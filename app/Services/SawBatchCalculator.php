<?php

namespace App\Services;

use App\Models\Needlist;
use App\Models\SawKriteria;
use App\Models\SawNilaiHistoris;
use App\Models\SawNilaiHistorisDetail;
use App\Models\SawPerhitungan;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use Carbon\Carbon;

/**
 * Menjalankan perhitungan SAW untuk kelompok (master barang x cluster
 * kendaraan x tier) dalam satu needlist. Dipakai baik oleh endpoint manual
 * "Hitung Ulang" maupun oleh halaman Pilih Supplier untuk mengisi otomatis
 * kelompok yang belum pernah dihitung saat halaman dibuka — supaya "Pilih"
 * tidak terkunci menunggu klik tombol (sesuai alur: sistem yang menjalankan
 * rekomendasi begitu syarat >1 supplier terpenuhi, bukan menunggu user).
 */
class SawBatchCalculator
{
    public function __construct(
        private SawService $sawService,
        private NeedlistSelectionGrouper $grouper,
    ) {}

    /**
     * Hitung SAW untuk semua kelompok (>=2 supplier) milik needlist ini.
     *
     * @param array $skipTierKeys tier_key yang dilewati (sudah pernah dihitung,
     *                            tidak perlu dihitung ulang kecuali eksplisit diminta)
     * @return array daftar hasil per kelompok yang berhasil diproses:
     *               [['panel_key'=>.., 'tier_key'=>.., 'auto_assigned'=>bool,
     *                 'perhitungan_id'=>.., 'recommended'=>[...], 'excluded'=>[...]], ...]
     */
    public function calculateForNeedlist(Needlist $needlist, array $skipTierKeys = []): array
    {
        $referenceVariasiIds = $needlist->details->where('is_reference', true)->pluck('id_variasi')->toArray();

        $groupedItems = $needlist->supplierInquiries
            ->flatMap(fn ($inq) => $inq->items->map(fn ($item) => [
                'supplier' => $inq->supplier,
                'inquiry'  => $inq,
                'item'     => $item,
                'master'   => $item->variasi->m_barang,
            ]))
            ->groupBy(fn ($x) => $x['master']->id_barang);

        $groups = $this->grouper->buildGroups($groupedItems, $referenceVariasiIds);

        $results = [];
        foreach ($groups as $group) {
            if ($group['unique_supplier_count'] < 2 || in_array($group['tier_key'], $skipTierKeys, true)) {
                continue;
            }

            try {
                $calc = $this->calculateGroup($needlist->id, $group['variasi_ids'], $group['master_barang_id']);
            } catch (\InvalidArgumentException $e) {
                // Masalah data per-kelompok (mis. penawaran belum lengkap, atau semua
                // kandidat dikecualikan karena belum punya historis) — lewati kelompok
                // ini saja, jangan gagalkan seluruh batch. Diserahkan ke input manual.
                continue;
            }
            // RuntimeException (mis. total bobot kriteria != 1.0) sengaja TIDAK
            // ditangkap di sini — itu masalah global (bukan per-kelompok), jadi
            // harus menghentikan seluruh batch dan naik ke pemanggil supaya
            // pesannya bisa ditampilkan apa adanya, bukan tertelan diam-diam.

            if ($calc['auto_assigned']) {
                // Hanya tersisa 1 kandidat (setelah exclude) — tetapkan langsung
                // tanpa SawService::calculate(), tidak ada SawPerhitungan yang dibuat.
                $only = $calc['candidate'];

                $results[] = [
                    'panel_key'      => $group['panel_key'],
                    'tier_key'       => $group['tier_key'],
                    'auto_assigned'  => true,
                    'perhitungan_id' => null,
                    'recommended'    => [
                        'supplier_id'    => $only['supplier_id'],
                        'id_variasi'     => $only['id_variasi'],
                        'nama'           => $only['nama'],
                        'harga'          => $only['C1'] ?? null,
                        'lead_time_hari' => $only['C3'] ?? null,
                        'estimasi_tiba'  => Carbon::now()->addDays((int) round($only['C3'] ?? 0))->format('d M Y'),
                        'sumber'         => 'auto_exclude',
                    ],
                    'excluded' => $calc['excluded'],
                ];
                continue;
            }

            $rek = $calc['ranked']->first();

            $results[] = [
                'panel_key'      => $group['panel_key'],
                'tier_key'       => $group['tier_key'],
                'auto_assigned'  => false,
                'perhitungan_id' => $calc['perhitungan_id'],
                'recommended'    => [
                    'supplier_id'    => $rek['supplier_id'],
                    'id_variasi'     => $rek['id_variasi'],
                    'nama'           => $rek['nama'],
                    'harga'          => $rek['nilai']['C1'] ?? null,
                    'lead_time_hari' => $rek['nilai']['C3'] ?? null,
                    'estimasi_tiba'  => Carbon::now()->addDays((int) round($rek['nilai']['C3'] ?? 0))->format('d M Y'),
                    'sumber'         => $rek['_has_historis'] ? 'historis' : 'penawaran',
                ],
                'excluded' => $calc['excluded'],
            ];
        }

        return $results;
    }

    /**
     * Tier key yang AMAN dilewati (tidak perlu dihitung ulang) saat
     * calculateForNeedlist() dipanggil — gabungan dari:
     *   - kelompok yang sudah dikonfirmasi user (UC-04, punya SawRekomendasi)
     *     → selalu skip, apa pun yang berubah setelahnya (keputusan terkunci).
     *   - kelompok yang belum dikonfirmasi TAPI datanya belum berubah sejak
     *     kalkulasi terakhir — dicek dari 3 sumber: harga/estimasi penawaran
     *     (supplier_inquiry_items), nilai kinerja supplier
     *     (saw_nilai_historis_detail), dan bobot/kriteria (saw_kriteria, global).
     *
     * Kelompok yang belum pernah dihitung sama sekali TIDAK masuk sini — itu
     * memang harus dihitung, bukan "aman di-skip".
     */
    public function determineSkipTierKeys(Needlist $needlist): array
    {
        $perhitungans = SawPerhitungan::where('needlist_id', $needlist->id)
            ->whereNotNull('tier_key')
            ->with(['details', 'rekomendasi'])
            ->get();

        $kriteriaUpdatedAt = SawKriteria::max('updated_at');

        $skip = [];
        foreach ($perhitungans as $p) {
            if ($p->rekomendasi) {
                $skip[] = $p->tier_key;
                continue;
            }

            $variasiIds  = $p->details->pluck('id_variasi')->filter()->unique()->values();
            $supplierIds = $p->details->pluck('supplier_id')->unique()->values();

            $inquiryUpdatedAt = SupplierInquiryItem::whereIn('id_variasi', $variasiIds)
                ->whereHas('inquiry', fn ($q) => $q->where('needlist_id', $needlist->id))
                ->max('updated_at');

            $historisUpdatedAt = SawNilaiHistorisDetail::whereHas(
                'historis', fn ($q) => $q->whereIn('supplier_id', $supplierIds)
            )->max('updated_at');

            $latestRelevant = collect([$inquiryUpdatedAt, $historisUpdatedAt, $kriteriaUpdatedAt])
                ->filter()
                ->map(fn ($v) => Carbon::parse($v))
                ->max();

            if ($latestRelevant === null || $latestRelevant->lte($p->calculated_at)) {
                $skip[] = $p->tier_key;
            }
        }

        return $skip;
    }

    /**
     * Hitung SAW untuk satu kelompok (kombinasi variasi yang dibandingkan bersama).
     * Tier key dihitung otomatis dari variasi_ids (md5 sorted), sama dengan yang
     * dipakai NeedlistSelectionGrouper, supaya hasilnya konsisten dengan lookup
     * di halaman Pilih Supplier.
     *
     * Kandidat (supplier) yang belum punya data historis DIKECUALIKAN dari
     * perbandingan (lihat mergeWithHistoris()). Tergantung berapa kandidat yang
     * tersisa setelah exclude:
     * - 0 tersisa  → InvalidArgumentException, diserahkan ke input manual.
     * - 1 tersisa  → tidak dihitung SAW (butuh minimal 2, lihat SawService::calculate()),
     *                langsung ditetapkan sebagai hasil ('auto_assigned' => true).
     * - >=2 tersisa → dihitung SAW seperti biasa.
     *
     * @throws \InvalidArgumentException kalau kandidat (variasi+supplier) awal < 2,
     *                                   atau tidak ada satupun kandidat tersisa setelah exclude
     */
    public function calculateGroup(int $needlistId, array $variasiIds, int $masterBarangId): array
    {
        $inquiryData = $this->getInquiryDataByCluster($needlistId, $variasiIds);

        if (count($inquiryData) < 2) {
            throw new \InvalidArgumentException(
                'SAW membutuhkan minimal 2 kombinasi variasi+supplier yang sudah mengisi penawaran untuk grup ini.'
            );
        }

        $merged   = $this->mergeWithHistoris($inquiryData);
        $eligible = array_values(array_filter($merged, fn ($row) => !$row['_excluded']));
        $excluded = array_values(array_filter($merged, fn ($row) => $row['_excluded']));

        $sorted = $variasiIds;
        sort($sorted);
        $tierKey = md5(implode(',', $sorted));

        if (count($eligible) === 0) {
            throw new \InvalidArgumentException(
                'Semua kandidat pada grup ini belum memiliki data historis kinerja supplier — perlu input manual.'
            );
        }

        if (count($eligible) === 1) {
            return [
                'auto_assigned' => true,
                'candidate'     => $eligible[0],
                'excluded'      => $excluded,
            ];
        }

        $idVariasiForRecord = count($variasiIds) === 1 ? $variasiIds[0] : null;

        $result             = $this->sawService->calculate($needlistId, $idVariasiForRecord, $eligible, $masterBarangId, $tierKey);
        $result['auto_assigned'] = false;
        $result['excluded']      = $excluded;

        return $result;
    }

    /**
     * Ambil data perbandingan untuk satu cluster (master+kendaraan+tier): setiap
     * kombinasi (variasi, supplier) yang sudah mengisi penawaran menjadi SATU
     * kandidat tersendiri — tidak digabung/dijumlah per supplier.
     *
     * Hanya ambil item yang:
     * - inquiry-nya berstatus 'responded'
     * - id_variasi termasuk dalam $variasiIds (anggota cluster/tier ini)
     * - harga_penawaran & estimasi_pengiriman sudah diisi
     */
    private function getInquiryDataByCluster(int $needlistId, array $variasiIds): array
    {
        if (empty($variasiIds)) {
            return [];
        }

        $inquiries = SupplierInquiry::with(['supplier', 'items' => function ($q) use ($variasiIds) {
            $q->whereIn('id_variasi', $variasiIds);
        }, 'items.variasi'])
            ->where('needlist_id', $needlistId)
            ->where('status', 'responded')
            ->get();

        $result = [];

        foreach ($inquiries as $inquiry) {
            foreach ($inquiry->items as $item) {
                if (!$item->variasi || is_null($item->harga_penawaran) || is_null($item->estimasi_pengiriman)) {
                    continue;
                }

                $leadTime = (int) max(1, abs(
                    Carbon::parse($item->estimasi_pengiriman)
                        ->diffInDays(Carbon::parse($inquiry->created_at))
                ));

                $result[] = [
                    'candidate_key' => $inquiry->supplier->id_supplier . '-' . $item->id_variasi,
                    'supplier_id'   => $inquiry->supplier->id_supplier,
                    'id_variasi'    => $item->id_variasi,
                    'nama'          => $inquiry->supplier->nama_supplier,
                    'nama_variasi'  => $item->variasi->nama_variasi,
                    'C1'            => (float) $item->harga_penawaran,
                    'C2'            => 0.0,   // akan diisi dari historis
                    'C3'            => (float) $leadTime,
                    'C4'            => 0.0,
                    'C5'            => 0.0,
                    'C6'            => 0.0,
                    '_has_historis' => false,
                    '_sumber_c1'    => 'inquiry',
                    '_sumber_c3'    => 'inquiry',
                ];
            }
        }

        // Deduplikasi kombinasi variasi+supplier (ambil harga terendah jika >1 inquiry)
        $byCandidate = [];
        foreach ($result as $row) {
            $key = $row['candidate_key'];
            if (!isset($byCandidate[$key]) || $row['C1'] < $byCandidate[$key]['C1']) {
                $byCandidate[$key] = $row;
            }
        }

        return array_values($byCandidate);
    }

    /**
     * Untuk setiap supplier dalam $inquiryData, cari data historis terbaru pada
     * tabel saw_nilai_historis.
     *
     * Supplier yang belum punya historis (baru / belum pernah dievaluasi)
     * DIKECUALIKAN dari perbandingan ($row['_excluded'] = true) — bukan diisi
     * nilai taksiran. Baris yang dikecualikan TETAP dikembalikan (supaya
     * pemanggil bisa menampilkan badge "belum ada riwayat"), tapi tidak boleh
     * dikirim ke SawService::calculate() — itu tanggung jawab pemanggil
     * (calculateGroup) untuk memfilternya berdasarkan flag ini.
     */
    private function mergeWithHistoris(array $inquiryData): array
    {
        // Semua kriteria di luar C1 (yang selalu dari inquiry) diinput manual
        // lewat form Kinerja Supplier dan tersimpan generik di
        // saw_nilai_historis_detail per kode — termasuk C2-C6.
        $kriteriaDinamis = SawKriteria::aktif()->where('kode', '!=', 'C1')->get();

        // Lookup 1x per supplier unik (bukan per baris) supaya supplier dengan
        // beberapa variasi dalam kelompok ini tidak query historis berulang.
        $historisBySupplier = [];
        foreach ($inquiryData as $row) {
            $supplierId = $row['supplier_id'];
            if (!array_key_exists($supplierId, $historisBySupplier)) {
                $historisBySupplier[$supplierId] = SawNilaiHistoris::where('supplier_id', $supplierId)
                    ->with('details')
                    ->orderByDesc('periode_akhir')
                    ->first();
            }
        }

        foreach ($inquiryData as &$row) {
            $historis = $historisBySupplier[$row['supplier_id']];

            if ($historis) {
                foreach ($kriteriaDinamis as $k) {
                    $detail = $historis->details->firstWhere('kriteria_id', $k->id);
                    $nilai  = (float) ($detail->nilai ?? 0);

                    if ($k->kode === 'C3') {
                        // C3: gunakan lead_time dari historis jika tersedia (>0),
                        // kalau tidak biarkan nilai dari inquiry (sudah di-set sebelumnya).
                        if ($nilai > 0) {
                            $row['C3']         = $nilai;
                            $row['_sumber_c3'] = 'historis';
                        }
                        continue;
                    }

                    $row[$k->kode] = $nilai;
                }

                $row['_has_historis'] = true;
                $row['_excluded']     = false;
            } else {
                // Belum ada historis — dikecualikan dari perbandingan SAW, bukan
                // diisi nilai taksiran (mean imputation).
                $row['_has_historis'] = false;
                $row['_excluded']     = true;
            }
        }
        unset($row);

        return $inquiryData;
    }
}
