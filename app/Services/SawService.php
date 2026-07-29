<?php

namespace App\Services;

use App\Models\SawKriteria;
use App\Models\SawPerhitungan;
use App\Models\SawPerhitunganDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SawService — Mesin perhitungan SAW (Simple Additive Weighting)
 *
 * Method utama: calculate()
 * Semua metode internal bersifat pure / framework-independent (tidak ada DB call
 * di dalam metode kalkulasi, hanya di saveToDatabase()).
 */
class SawService
{
    /**
     * Jalankan perhitungan SAW lengkap untuk satu variasi dalam satu needlist.
     *
     * @param  int    $needlistId
     * @param  int    $idVariasi
     * @param  array  $supplierData  Format: lihat docblock di bawah
     * @return array  Hasil perhitungan lengkap beserta metadata
     *
     * Format $supplierData:
     * [
     *   [
     *     'supplier_id'    => int,
     *     'nama'           => string,
     *     'C1'             => float,   // Total Biaya (Rp)
     *     'C2'             => float,   // Termin Pembayaran (hari)
     *     'C3'             => float,   // Lead Time (hari)
     *     'C4'             => float,   // Akurasi Kuantitas (%)
     *     'C5'             => float,   // Tingkat Pemenuhan (%)
     *     'C6'             => float,   // Komunikasi (1-5)
     *     '_has_historis'  => bool,
     *     '_sumber_c1'     => string,  // 'inquiry'|'historis'|'manual'
     *     '_sumber_c3'     => string,
     *   ],
     *   ...
     * ]
     */
    public function calculate(
        int $needlistId,
        ?int $idVariasi,
        array $supplierData,
        ?int $idBarang = null,
        ?string $tierKey = null
    ): array {
        // 1. Ambil kriteria aktif
        $kriterias = SawKriteria::aktif()->get();

        // 2. Validasi bobot = 1.0
        $this->validateBobot($kriterias);

        // 3. Validasi minimal 2 supplier
        if (count($supplierData) < 2) {
            throw new \InvalidArgumentException(
                'SAW membutuhkan minimal 2 supplier untuk perbandingan.'
            );
        }

        // 4. Bangun matriks keputusan Xij
        $matrix = $this->buildMatrix($supplierData, $kriterias);

        // 5. Normalisasi → Rij
        $normalized = $this->normalize($matrix, $kriterias);

        // 6. Hitung Vi (weighted sum)
        $viScores = $this->weightedSum($normalized, $kriterias, $supplierData);

        // 7. Ranking
        $ranked = $this->rank($viScores);

        // 8. Simpan ke DB
        $perhitungan = $this->saveToDatabase(
            $needlistId, $idVariasi, $kriterias,
            $supplierData, $matrix, $normalized, $ranked, $idBarang, $tierKey
        );

        return [
            'perhitungan_id'  => $perhitungan->id,
            'tier_key'        => $tierKey,
            'kriterias'       => $kriterias->toArray(),
            'supplier_data'   => $supplierData,
            'matrix'          => $matrix,
            'normalized'      => $normalized,
            'ranked'          => $ranked,
            'rekomendasi'     => $ranked->first(),
        ];
    }

    // =========================================================================
    // PRIVATE — KALKULASI
    // =========================================================================

    /**
     * Kunci unik tiap kandidat dalam matriks.
     *
     * Default-nya supplier_id (mode lama: 1 kandidat = 1 supplier).
     * Jika $supplierData menyertakan 'candidate_key' (mode kombinasi
     * variasi+supplier), gunakan itu agar 1 supplier bisa muncul sebagai
     * beberapa kandidat berbeda (satu per variasi yang ditawarkan).
     */
    private function candidateKey(array $s): string
    {
        return (string) ($s['candidate_key'] ?? $s['supplier_id']);
    }

    /**
     * Bangun matriks keputusan: [ candidate_key => [ 'C1'=>x, 'C2'=>x, ... ] ]
     */
    private function buildMatrix(array $supplierData, Collection $kriterias): array
    {
        $matrix = [];
        foreach ($supplierData as $s) {
            $row = [];
            foreach ($kriterias as $k) {
                $row[$k->kode] = (float) ($s[$k->kode] ?? 0);
            }
            $matrix[$this->candidateKey($s)] = $row;
        }
        return $matrix;
    }

    /**
     * Normalisasi matriks:
     *   BENEFIT → rij = xij / max(xij)
     *   COST    → rij = min(xij) / xij
     * Guard: jika nilai 0 atau semua sama, hasilkan 1.
     */
    private function normalize(array $matrix, Collection $kriterias): array
    {
        $normalized = [];

        foreach ($kriterias as $k) {
            $kode   = $k->kode;
            $values = array_column($matrix, $kode);

            $maxVal = max($values);
            $minVal = min($values);

            foreach ($matrix as $supplierId => $row) {
                $x = $row[$kode];

                if ($k->isBenefit()) {
                    $rij = ($maxVal > 0) ? round($x / $maxVal, 6) : 1.0;
                } else {
                    // COST
                    $rij = ($x > 0) ? round($minVal / $x, 6) : 1.0;
                }

                $normalized[$supplierId][$kode] = $rij;
            }
        }

        return $normalized;
    }

    /**
     * Hitung Vi = Σ (Wj × Rij) untuk setiap supplier.
     * Kembalikan Collection berisi array per supplier dengan semua detail.
     */
    private function weightedSum(
        array $normalized,
        Collection $kriterias,
        array $supplierData
    ): Collection {
        $candidateMap = collect($supplierData)->keyBy(fn ($s) => $this->candidateKey($s));

        $results = collect();

        foreach ($normalized as $candidateKey => $normRow) {
            $vi       = 0.0;
            $weighted = [];

            foreach ($kriterias as $k) {
                $w = (float) $k->bobot;
                $r = $normRow[$k->kode];
                $wk = round($w * $r, 6);
                $weighted[$k->kode] = $wk;
                $vi += $wk;
            }

            $candidate = $candidateMap->get($candidateKey);

            $results->push([
                'candidate_key' => $candidateKey,
                'supplier_id'   => $candidate['supplier_id'] ?? null,
                'id_variasi'    => $candidate['id_variasi'] ?? null,
                'nama'          => $candidate['nama'] ?? '-',
                'nama_variasi'  => $candidate['nama_variasi'] ?? null,
                '_has_historis' => $candidate['_has_historis'] ?? false,
                '_sumber_c1'    => $candidate['_sumber_c1'] ?? 'inquiry',
                '_sumber_c3'    => $candidate['_sumber_c3'] ?? 'inquiry',
                'nilai'         => $candidate,         // raw Xij
                'normalized'    => $normRow,           // Rij
                'weighted'      => $weighted,          // Wj × Rij
                'nilai_vi'      => round($vi, 6),
            ]);
        }

        return $results;
    }

    /**
     * Ranking berdasarkan nilai Vi tertinggi.
     */
    private function rank(Collection $viScores): Collection
    {
        $sorted = $viScores->sortByDesc('nilai_vi')->values();

        return $sorted->map(function ($item, $index) {
            $item['ranking']        = $index + 1;
            $item['is_recommended'] = ($index === 0) ? 1 : 0;
            return $item;
        });
    }

    // =========================================================================
    // PRIVATE — DATABASE
    // =========================================================================

    private function saveToDatabase(
        int $needlistId,
        ?int $idVariasi,
        Collection $kriterias,
        array $supplierData,
        array $matrix,
        array $normalized,
        Collection $ranked,
        ?int $idBarang = null,
        ?string $tierKey = null
    ): SawPerhitungan {
        return DB::transaction(function () use (
            $needlistId, $idVariasi, $idBarang, $tierKey,
            $kriterias, $supplierData, $matrix, $normalized, $ranked
        ) {
            // Upsert: hapus perhitungan lama dengan key yang sama
            $query = SawPerhitungan::where('needlist_id', $needlistId);
            if ($idBarang && $tierKey) {
                // Per master barang + tier cluster (paling presisi — tidak overwrite tier lain)
                $query->where('id_barang', $idBarang)->where('tier_key', $tierKey);
            } elseif ($idBarang) {
                $query->where('id_barang', $idBarang)->whereNull('tier_key');
            } else {
                $query->where('id_variasi', $idVariasi);
            }
            $old = $query->first();

            if ($old) {
                $old->details()->delete();
                $old->delete();
            }

            // Snapshot bobot
            $bobotSnapshot = $kriterias->map(fn ($k) => [
                'kode'   => $k->kode,
                'nama'   => $k->nama,
                'jenis'  => $k->jenis,
                'bobot'  => $k->bobot,
            ])->toArray();

            $perhitungan = SawPerhitungan::create([
                'needlist_id'    => $needlistId,
                'id_variasi'     => $idVariasi,
                'id_barang'      => $idBarang,
                'tier_key'       => $tierKey,
                'bobot_snapshot' => $bobotSnapshot,
                'status'         => 'draft',
                'calculated_at'  => now(),
                'calculated_by'  => Auth::id(),
            ]);

            $candidateMap = collect($supplierData)->keyBy(fn ($s) => $this->candidateKey($s));

            foreach ($ranked as $row) {
                $candidateKey = $row['candidate_key'];
                $candidate    = $candidateMap->get($candidateKey);
                $rawValues    = $matrix[$candidateKey];
                $normValues   = $normalized[$candidateKey];

                SawPerhitunganDetail::create([
                    'perhitungan_id' => $perhitungan->id,
                    'supplier_id'    => $row['supplier_id'],
                    'id_variasi'     => $row['id_variasi'],

                    'nilai_c1' => $rawValues['C1'],
                    'nilai_c2' => $rawValues['C2'],
                    'nilai_c3' => $rawValues['C3'],
                    'nilai_c4' => $rawValues['C4'],
                    'nilai_c5' => $rawValues['C5'],
                    'nilai_c6' => $rawValues['C6'],

                    'norm_c1' => $normValues['C1'],
                    'norm_c2' => $normValues['C2'],
                    'norm_c3' => $normValues['C3'],
                    'norm_c4' => $normValues['C4'],
                    'norm_c5' => $normValues['C5'],
                    'norm_c6' => $normValues['C6'],

                    'weighted_c1' => $row['weighted']['C1'],
                    'weighted_c2' => $row['weighted']['C2'],
                    'weighted_c3' => $row['weighted']['C3'],
                    'weighted_c4' => $row['weighted']['C4'],
                    'weighted_c5' => $row['weighted']['C5'],
                    'weighted_c6' => $row['weighted']['C6'],

                    'nilai_vi'       => $row['nilai_vi'],
                    'ranking'        => $row['ranking'],
                    'is_recommended' => $row['is_recommended'],
                    'sumber_c1'      => $candidate['_sumber_c1'] ?? 'inquiry',
                    'sumber_c3'      => $candidate['_sumber_c3'] ?? 'inquiry',
                    'has_historis'   => $candidate['_has_historis'] ?? false,
                ]);
            }

            return $perhitungan;
        });
    }

    // =========================================================================
    // PRIVATE — VALIDASI
    // =========================================================================

    private function validateBobot(Collection $kriterias): void
    {
        $totalBobot = $kriterias->sum('bobot');
        $tolerance  = 0.0001;

        if (abs($totalBobot - 1.0) > $tolerance) {
            throw new \RuntimeException(
                sprintf(
                    'Total bobot kriteria harus = 1.0, saat ini = %.4f. ' .
                    'Silakan periksa dan sesuaikan bobot di halaman Kriteria & Bobot.',
                    $totalBobot
                )
            );
        }
    }
}
