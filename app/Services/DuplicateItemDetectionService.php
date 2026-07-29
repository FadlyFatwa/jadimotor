<?php

namespace App\Services;

use App\Models\ItemDuplicateMerge;
use App\Models\SupplierVariasi;
use App\Models\Variasi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Deteksi item duplikat memakai union-find 2 tier:
 * Tier 1 (selalu jalan, O(n)) - signature kata terurut, exact match lewat hashmap.
 * Tier 2 (opsional) - per kategori, blocking lewat kata paling jarang (bukan kata pertama),
 * lalu fuzzy match similar_text() di dalam bucket kecil, dengan pre-filter rasio panjang string.
 * Diadaptasi dari implementasi yang sudah divalidasi di sistem toko sparepart sebelah (~16rb item, ~0.4s).
 */
class DuplicateItemDetectionService
{
    private const FUZZY_BUCKET_LIMIT = 150;

    private const GRADE_TIER_MAP = [
        'G' => 'Original',
        'B' => 'KW',
        'L' => 'Lelangan',
        'AFTERMARKET' => 'Aftermarket',
    ];

    /**
     * Pilihan tier untuk dropdown override manual di form kategorisasi. Sub-grade Aftermarket
     * (A/B/C) sengaja TIDAK dideteksi otomatis dari teks (tidak ada sinyal tekstual yang
     * membedakan kualitas antar merk Aftermarket) — cuma data pilihan, admin yang menentukan
     * sendiri per item kalau perlu lebih spesifik dari "Aftermarket" polos.
     */
    public const TIER_OPTIONS = [
        'OEM', 'Original', 'Aftermarket', 'Aftermarket A', 'Aftermarket B', 'Aftermarket C', 'KW', 'Lelangan',
    ];

    /**
     * @return array<int, array<int, Variasi>> daftar cluster (tiap cluster minimal 2 item),
     *         item dalam cluster sudah diurutkan stock terbanyak -> tersedikit.
     */
    public function detect(bool $includeFuzzy = true, float $threshold = 85.0): array
    {
        $items = Variasi::active()
            ->with(['m_barang.kategori', 'suppliervariasi.supplier', 'vehicleGenerations'])
            ->get()->values();

        return $this->detectSimilarItems($items, $threshold, $includeFuzzy);
    }

    private function detectSimilarItems(Collection $items, float $threshold, bool $includeFuzzy): array
    {
        $parent = range(0, $items->count() - 1);

        $find = function (int $x) use (&$parent, &$find) {
            while ($parent[$x] !== $x) {
                $x = $parent[$x];
            }
            return $x;
        };

        $union = function (int $a, int $b) use (&$parent, $find) {
            $ra = $find($a);
            $rb = $find($b);
            if ($ra !== $rb) {
                $parent[$ra] = $rb;
            }
        };

        $norm = [];
        $sig = [];
        $words = [];
        $grade = [];

        foreach ($items as $i => $item) {
            $norm[$i] = $this->normalizeName($item->nama_variasi);
            $sig[$i] = $this->sortedSignature($item->nama_variasi);
            $words[$i] = array_values(array_filter(explode(' ', $norm[$i]), fn ($w) => $w !== ''));
            $grade[$i] = $this->extractGradeTag($item->nama_variasi);
        }

        // GUARD: item dengan tag grade berbeda ('G'/'B'/'L'/tanpa-tag) tidak boleh pernah
        // disatukan, walau nama sisanya identik/mirip — itu memang produk berbeda (lihat
        // Bagian 2: Original/KW/Lelangan/Aftermarket tidak boleh ke-merge satu sama lain).
        $sameGrade = fn (int $i, int $j) => $grade[$i] === $grade[$j];

        // TIER 1 — signature kata terurut identik = grup yang sama (hashmap, bukan O(n^2)),
        // tapi tetap dipecah per grade dulu sebelum disatukan.
        $sigBuckets = [];
        foreach ($sig as $i => $s) {
            $sigBuckets[$s][] = $i;
        }
        foreach ($sigBuckets as $indices) {
            if (count($indices) < 2) {
                continue;
            }
            $byGrade = [];
            foreach ($indices as $i) {
                $byGrade[$grade[$i]][] = $i;
            }
            foreach ($byGrade as $gradeIndices) {
                if (count($gradeIndices) < 2) {
                    continue;
                }
                for ($k = 1, $cnt = count($gradeIndices); $k < $cnt; $k++) {
                    $union($gradeIndices[0], $gradeIndices[$k]);
                }
            }
        }

        // TIER 2 — fuzzy match per kategori, blocking lewat kata paling jarang
        if ($includeFuzzy) {
            $byCategory = [];
            foreach ($items as $i => $item) {
                $catId = $item->m_barang->id_kategori ?? 0;
                $byCategory[$catId][] = $i;
            }

            foreach ($byCategory as $indices) {
                $df = [];
                foreach ($indices as $i) {
                    foreach (array_unique($words[$i]) as $w) {
                        $df[$w] = ($df[$w] ?? 0) + 1;
                    }
                }

                $inverted = [];
                foreach ($indices as $i) {
                    if (empty($words[$i])) {
                        continue;
                    }
                    $rarest = $words[$i][0];
                    $rarestDf = $df[$rarest] ?? PHP_INT_MAX;
                    foreach ($words[$i] as $w) {
                        if (($df[$w] ?? 0) < $rarestDf) {
                            $rarest = $w;
                            $rarestDf = $df[$w];
                        }
                    }
                    $inverted[$rarest][] = $i;
                }

                foreach ($inverted as $bucket) {
                    $count = count($bucket);
                    if ($count < 2 || $count > self::FUZZY_BUCKET_LIMIT) {
                        continue;
                    }

                    for ($a = 0; $a < $count; $a++) {
                        for ($b = $a + 1; $b < $count; $b++) {
                            $i = $bucket[$a];
                            $j = $bucket[$b];
                            if ($find($i) === $find($j)) {
                                continue;
                            }
                            if (!$sameGrade($i, $j)) {
                                continue;
                            }

                            $lenI = strlen($norm[$i]);
                            $lenJ = strlen($norm[$j]);
                            $maxLen = max($lenI, $lenJ);
                            if ($maxLen === 0) {
                                continue;
                            }
                            if ((min($lenI, $lenJ) / $maxLen) * 100 < $threshold) {
                                continue;
                            }

                            similar_text($norm[$i], $norm[$j], $percent);
                            if ($percent >= $threshold) {
                                $union($i, $j);
                            }
                        }
                    }
                }
            }
        }

        // TIER 3 — item yang SUDAH dikategorikan (id_barang bukan "BELUM DIKATEGORIKAN") tetap
        // dicek ulang: kalau master barang sama, grade sama, dan berbagi minimal 1 kecocokan
        // generasi kendaraan, plus nama variasi (yang mungkin sudah dibersihkan & jadi pendek)
        // masih mirip secara teks -> tetap dianggap kandidat duplikat. Additive terhadap Tier
        // 1/2 di atas, tidak mengubah hasil mereka — ini menjawab kasus item yang sudah
        // dikategorikan jadi tidak lagi terlihat mirip secara signature/panjang teks mentah.
        $byMasterGrade = [];
        foreach ($items as $i => $item) {
            $namaBarang = $item->m_barang->nama_barang ?? '';
            if ($item->id_barang === null || stripos($namaBarang, 'belum dikategorikan') !== false) {
                continue;
            }
            $byMasterGrade[$item->id_barang . '|' . $grade[$i]][] = $i;
        }

        foreach ($byMasterGrade as $indices) {
            $count = count($indices);
            if ($count < 2) {
                continue;
            }

            $genSets = [];
            foreach ($indices as $i) {
                $genSets[$i] = $items[$i]->vehicleGenerations->pluck('id')->toArray();
            }

            for ($a = 0; $a < $count; $a++) {
                for ($b = $a + 1; $b < $count; $b++) {
                    $i = $indices[$a];
                    $j = $indices[$b];
                    if ($find($i) === $find($j)) {
                        continue;
                    }
                    if (empty(array_intersect($genSets[$i], $genSets[$j]))) {
                        continue;
                    }

                    similar_text($norm[$i], $norm[$j], $percent);
                    if ($percent >= $threshold) {
                        $union($i, $j);
                    }
                }
            }
        }

        $clusters = [];
        foreach ($items as $i => $item) {
            $clusters[$find($i)][] = $item;
        }

        $clusters = array_values(array_filter($clusters, fn ($c) => count($c) > 1));

        foreach ($clusters as &$cluster) {
            usort($cluster, fn ($a, $b) => ($b->stock ?? 0) <=> ($a->stock ?? 0));
        }

        return $clusters;
    }

    /**
     * Gabungkan beberapa item ke satu item target: stok dijumlahkan ke target,
     * item lain dinonaktifkan (bukan dihapus, riwayat transaksi tetap utuh), harga/supplier
     * dipindah (upsert, bukan overwrite) ke target, dan setiap pasangan dicatat ke arsip
     * item_duplicate_merges supaya bisa ditrack barcode mana yang digabung ke barcode target.
     */
    public function merge(int $targetId, array $mergeIds, ?int $userId = null): Variasi
    {
        $mergeIds = array_values(array_unique(array_diff($mergeIds, [$targetId])));

        if (empty($mergeIds)) {
            throw new InvalidArgumentException('Tidak ada item lain yang dipilih untuk digabungkan.');
        }

        return DB::transaction(function () use ($targetId, $mergeIds, $userId) {
            $target = Variasi::lockForUpdate()->findOrFail($targetId);
            $others = Variasi::with('suppliervariasi')
                ->whereIn('id_variasi', $mergeIds)
                ->lockForUpdate()
                ->get();

            foreach ($others as $other) {
                $stockMoved = (float) ($other->stock ?? 0);

                foreach ($other->suppliervariasi as $sv) {
                    $alreadyHasSupplier = SupplierVariasi::where('id_variasi', $target->id_variasi)
                        ->where('id_supplier', $sv->id_supplier)
                        ->exists();

                    if (!$alreadyHasSupplier) {
                        SupplierVariasi::create([
                            'id_variasi' => $target->id_variasi,
                            'id_supplier' => $sv->id_supplier,
                            'harga_list' => $sv->harga_list,
                            'kode_list' => $sv->kode_list,
                            'harga_beli' => $sv->harga_beli,
                            'kode_beli' => $sv->kode_beli,
                            'diskon' => $sv->diskon,
                        ]);
                    }
                }

                ItemDuplicateMerge::create([
                    'target_id_variasi' => $target->id_variasi,
                    'target_barcode' => $target->barcode,
                    'merged_id_variasi' => $other->id_variasi,
                    'merged_barcode' => $other->barcode,
                    'merged_nama_variasi' => $other->nama_variasi,
                    'stock_moved' => $stockMoved,
                    'merged_by' => $userId,
                    'merged_at' => now(),
                ]);

                $target->stock = (float) ($target->stock ?? 0) + $stockMoved;
                $other->stock = 0;
                $other->is_active = false;
                $this->applyGradeTier($other);
                $other->save();
            }

            $this->applyGradeTier($target);
            $target->save();

            return $target;
        });
    }

    /**
     * Tag grade dalam nama_variasi ('G'/'B'/'L'/tanpa-tag) belum pernah dipetakan ke kolom
     * `tier` (OEM/Original/Aftermarket/KW/Lelangan) — kolomnya selalu NULL di data nyata.
     * Dipanggil setiap kali item diproses lewat tool ini (merge maupun kategorisasi) supaya
     * tier ikut terisi otomatis, bukan cuma tampil sebagai badge di UI. Tidak menimpa tier yang
     * SUDAH terisi — kalau item baru saja dikategorikan dengan sub-grade manual (mis.
     * "Aftermarket A"), merge() yang dipanggil setelahnya tidak boleh menimpa balik ke
     * "Aftermarket" polos hasil deteksi otomatis dari tag teks.
     */
    public function applyGradeTier(Variasi $item): void
    {
        if (!empty($item->tier)) {
            return;
        }

        $tier = self::GRADE_TIER_MAP[$this->extractGradeTag($item->nama_variasi)] ?? null;
        if ($tier !== null) {
            $item->tier = $tier;
        }
    }

    /**
     * Ekstrak tag grade 1-huruf dalam tanda kutip dari nama asli (sebelum dinormalisasi,
     * karena normalizeName() membuang tanda kutipnya). Tanpa tag = 'AFTERMARKET'.
     * Lihat catatan Bagian 2: 'G'=Original, 'B'=KW, 'L'=Lelangan, tanpa tag=Aftermarket.
     * Public supaya bisa dipakai juga untuk menampilkan badge grade di UI.
     */
    public function extractGradeTag(?string $name): string
    {
        if ($name && preg_match("/'([A-Za-z])'/", $name, $m)) {
            return strtoupper($m[1]);
        }

        return 'AFTERMARKET';
    }

    private function normalizeName(?string $name): string
    {
        $name = strtolower((string) $name);
        $name = preg_replace('/[^a-z0-9\s]/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }

    private function sortedSignature(?string $name): string
    {
        $words = explode(' ', $this->normalizeName($name));
        sort($words);
        return implode(' ', $words);
    }
}
