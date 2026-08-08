<?php

namespace App\Http\Controllers\SupplierSelection;

use App\Http\Controllers\Controller;
use App\Models\Needlist;
use App\Models\SawNilaiHistoris;
use App\Models\SawPerhitungan;
use App\Services\NeedlistSelectionGrouper;
use App\Services\SawBatchCalculator;
use App\Services\SupplierLeadTimeResolver;

class SupplierRecommendationController extends Controller
{
    public function __construct(
        private NeedlistSelectionGrouper $grouper,
        private SupplierLeadTimeResolver $leadTimeResolver,
        private SawBatchCalculator $batchCalculator,
    ) {}

    /**
     * GET /procurement/pemilihan-supplier
     * Daftar needlist yang sedang di tahap Pemilihan Supplier.
     */
    public function index()
    {
        $needlists = Needlist::with([
            'user',
            'details',
            'supplierInquiries.supplier',
            'supplierInquiries.items.variasi.m_barang',
            'supplierInquiries.items.variasi.vehicleGenerations.vehicle',
        ])
            ->where('status', 'selection_in_progress')
            ->latest()
            ->get();

        // Progres pemilihan per needlist, dihitung per KELOMPOK (bukan per variasi)
        // — pengelompokan sama persis dengan yang dipakai show(), supaya konsisten.
        // Alasan tidak bisa per-variasi: dalam satu kelompok, beberapa variasi bisa
        // jadi alternatif yang saling bersaing (mis. dua supplier menawarkan variasi
        // berbeda untuk kebutuhan yang sama) — cuma SATU yang akan dipilih, sisanya
        // memang wajar tetap 'pending' selamanya. Menghitung per variasi salah
        // mengira alternatif yang kalah itu "belum dipilih".
        $needlists->each(function (Needlist $needlist) {
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

            $needlist->pemilihanStatus = $this->statusPemilihanDariGroups($groups);
        });

        return view('pages.procurement.pemilihan_supplier.index', compact('needlists'));
    }

    /**
     * GET /procurement/pemilihan-supplier/{needlist}/ringkasan
     * Ringkasan sebelum masuk ke pemilihan: daftar item kebutuhan (kiri) dan
     * rekap supplier yang terlibat di needlist ini (kanan, tidak per-item).
     */
    public function ringkasan($id)
    {
        $needlist = Needlist::with([
            'details.variasi.m_barang.kategori',
            'details.variasi.vehicleGenerations.vehicle',
            'details.variasi.suppliervariasi.supplier',
            'supplierInquiries.supplier',
            'supplierInquiries.items',
        ])->findOrFail($id);

        $items = $needlist->details->where('is_reference', false)->values();

        $suppliers = $needlist->supplierInquiries
            ->pluck('supplier')
            ->filter()
            ->unique('id_supplier')
            ->sortBy('nama_supplier')
            ->values();

        // Variasi yang sudah ada minimal 1 supplier mengonfirmasi harga (harga_penawaran terisi).
        $confirmedVariasiIds = $needlist->supplierInquiries
            ->flatMap(fn ($inq) => $inq->items)
            ->whereNotNull('harga_penawaran')
            ->pluck('id_variasi')
            ->unique();

        return view('pages.procurement.pemilihan_supplier.ringkasan', compact(
            'needlist', 'items', 'suppliers', 'confirmedVariasiIds'
        ));
    }

    /**
     * GET /procurement/pemilihan-supplier/{needlist}
     * Detail pemilihan supplier untuk satu needlist: item, supplier yang tersedia,
     * dan kelompok perbandingan SAW (master barang x cluster kendaraan x tier).
     */
    public function show($id)
    {
        $needlist = Needlist::with([
            'details.variasi.m_barang.kategori',
            'details.variasi.vehicleGenerations.vehicle',
            'details.variasi.suppliervariasi',
            'supplierInquiries.supplier',
            'supplierInquiries.items.variasi.m_barang.kategori',
            'supplierInquiries.items.variasi.vehicleGenerations.vehicle',
        ])->findOrFail($id);

        $referenceVariasiIds = $needlist->details
            ->where('is_reference', true)
            ->pluck('id_variasi')
            ->toArray();

        $groupedItems = $needlist->supplierInquiries
            ->flatMap(fn ($inq) => $inq->items->map(fn ($item) => [
                'supplier' => $inq->supplier,
                'inquiry'  => $inq,
                'item'     => $item,
                'master'   => $item->variasi->m_barang,
            ]))
            ->groupBy(fn ($x) => $x['master']->id_barang);

        $groups = $this->grouper->buildGroups($groupedItems, $referenceVariasiIds);

        // Sudah "Sudah Dipilih" lengkap SEBELUM kunjungan ini (state di DB saat
        // halaman dibuka) — dipakai buat peringatkan user kalau mau menimpa
        // pilihan yang sebelumnya sudah final, bukan buat menahan aksinya.
        $sudahDipilihLengkap = $this->statusPemilihanDariGroups($groups) === 'sudah_dipilih';

        // Kelompok yang AMAN dilewati (tidak dihitung ulang): sudah dikonfirmasi
        // user (UC-04, terkunci permanen), ATAU belum dikonfirmasi tapi datanya
        // (harga/estimasi penawaran, nilai kinerja supplier, bobot kriteria) belum
        // berubah sejak kalkulasi terakhir. Tidak ada lagi tombol "Hitung Ulang"
        // manual — sistem yang menentukan sendiri kapan perlu hitung ulang.
        $alreadyComputedTierKeys = $this->batchCalculator->determineSkipTierKeys($needlist);

        // Otomatis hitung kelompok yang belum pernah dihitung ATAU datanya sudah
        // berubah, supaya tombol "Pilih" tidak terkunci menunggu aksi manual —
        // sesuai alur: begitu syarat >1 supplier terpenuhi, sistem yang menjalankan
        // rekomendasi, bukan menunggu permintaan eksplisit dari user.
        // Tidak dijalankan kalau PO sudah terbit — tidak ada lagi yang perlu dihitung.
        //
        // Kelompok yang berujung 'auto_assigned' (exclude menyisakan 1 kandidat)
        // TIDAK membuat baris SawPerhitungan, jadi tidak pernah masuk
        // $alreadyComputedTierKeys — otomatis dihitung ulang setiap kali halaman
        // dibuka (murah, cuma lookup + filter, bukan kalkulasi SAW penuh).
        $sawError = null;
        $autoAssignedByTierKey = collect();
        if (!in_array($needlist->status, ['po_issued', 'completed'], true)) {
            try {
                $batchResults = $this->batchCalculator->calculateForNeedlist($needlist, $alreadyComputedTierKeys);
                $autoAssignedByTierKey = collect($batchResults)
                    ->where('auto_assigned', true)
                    ->keyBy('tier_key');
            } catch (\RuntimeException $e) {
                // Masalah global (mis. total bobot kriteria aktif belum 100%) —
                // tampilkan apa adanya supaya jelas bedanya dengan "data harga belum lengkap".
                $sawError = $e->getMessage();
            }
        }

        $perhitunganByTierKey = SawPerhitungan::where('needlist_id', $needlist->id)
            ->whereNotNull('tier_key')
            ->with('details.supplier')
            ->get()
            ->keyBy('tier_key');

        // Supplier tanpa data historis sama sekali — dipakai view untuk menandai
        // baris yang dikecualikan dari perbandingan SAW ("belum ada riwayat"),
        // terlepas dari kelompok mana pun (fakta di level supplier, bukan per grup).
        $supplierIdsWithHistoris = SawNilaiHistoris::pluck('supplier_id')->unique()->flip();

        $leadTimeResolver = $this->leadTimeResolver;

        return view('pages.procurement.pemilihan_supplier.show', compact(
            'needlist',
            'groups',
            'perhitunganByTierKey',
            'leadTimeResolver',
            'sawError',
            'autoAssignedByTierKey',
            'supplierIdsWithHistoris',
            'sudahDipilihLengkap'
        ));
    }

    /**
     * Status progres pemilihan dari kumpulan kelompok (dipakai index() untuk
     * badge per needlist, dan show() untuk deteksi "sudah final sebelumnya").
     * Dihitung per KELOMPOK (bukan per variasi) — dalam satu kelompok bisa ada
     * beberapa variasi yang jadi alternatif saling bersaing, cuma satu yang akan
     * terpilih, sisanya wajar tetap 'pending' selamanya.
     */
    private function statusPemilihanDariGroups(array $groups): string
    {
        $groupsNeedingSelection = collect($groups)->filter(fn ($g) => $g['unique_supplier_count'] >= 1);

        $totalGroups    = $groupsNeedingSelection->count();
        $selectedGroups = $groupsNeedingSelection->filter(
            fn ($g) => $g['rows']->contains(fn ($r) => $r['item']->status === 'selected')
        )->count();

        return match (true) {
            $totalGroups === 0             => 'belum_konfirmasi',
            $selectedGroups === 0          => 'belum_dipilih',
            $selectedGroups < $totalGroups => 'sebagian_dipilih',
            default                        => 'sudah_dipilih',
        };
    }
}
