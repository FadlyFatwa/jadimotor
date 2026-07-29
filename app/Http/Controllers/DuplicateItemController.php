<?php

namespace App\Http\Controllers;

use App\Models\ItemCategorizationLog;
use App\Models\ItemDuplicateMerge;
use App\Models\MBarang;
use App\Models\ProductVariantCompatibility;
use App\Models\Variasi;
use App\Models\VehicleGeneration;
use App\Services\DuplicateItemDetectionService;
use App\Services\ItemNameParserService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DuplicateItemController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private DuplicateItemDetectionService $service,
        private ItemNameParserService $parser
    ) {
    }

    public function index(Request $request)
    {
        $includeFuzzy = $request->boolean('fuzzy', true);
        $barcodeSearch = trim((string) $request->input('barcode', ''));

        $clusters = $this->service->detect($includeFuzzy);

        if ($barcodeSearch !== '') {
            $clusters = array_values(array_filter(
                $clusters,
                fn ($cluster) => collect($cluster)->contains(
                    fn ($item) => stripos((string) $item->barcode, $barcodeSearch) !== false
                )
            ));
        }

        $total = count($clusters);

        $page = max(1, (int) $request->input('page', 1));
        $pageClusters = array_slice($clusters, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        $groups = collect($pageClusters)->map(function ($cluster) {
            $items = collect($cluster)->map(function ($item) {
                return [
                    'id_variasi' => $item->id_variasi,
                    'barcode' => $item->barcode,
                    'nama_variasi' => $item->nama_variasi,
                    'nama_barang' => $item->m_barang->nama_barang ?? '-',
                    'kategori' => $item->m_barang->kategori->nama_kategori ?? '-',
                    'part_number' => $item->part_number,
                    'tier' => $item->tier,
                    'grade' => $this->service->extractGradeTag($item->nama_variasi),
                    'stock' => (float) ($item->stock ?? 0),
                    'suppliers' => $item->suppliervariasi->map(fn ($sv) => [
                        'nama_supplier' => $sv->supplier->nama_supplier ?? '-',
                        'harga_beli' => (int) ($sv->harga_beli ?? 0),
                    ])->values(),
                ];
            })->values();

            $targetItem = $cluster[0];
            $defaultTargetId = $targetItem->id_variasi;

            return [
                'items' => $items,
                'default_target_id' => $defaultTargetId,
                'category_suggestion' => $this->buildCategorySuggestion($cluster, $targetItem),
            ];
        })->values();

        $paginator = new LengthAwarePaginator(
            $groups,
            $total,
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('pages.duplikat_item.index', [
            'groups' => $paginator,
            'includeFuzzy' => $includeFuzzy,
            'barcodeSearch' => $barcodeSearch,
            'vehicleOptions' => $this->parser->vehicleOptionsGrouped(),
            'mbarangNames' => MBarang::orderBy('nama_barang')->pluck('nama_barang'),
            'tierOptions' => DuplicateItemDetectionService::TIER_OPTIONS,
        ]);
    }

    /**
     * Saran kategorisasi (calon nama_barang, no part, kecocokan kendaraan) hanya ditampilkan
     * untuk grup yang item targetnya masih duduk di bucket "BELUM DIKATEGORIKAN" — item yang
     * sudah punya MBarang yang benar tidak perlu disarankan ulang.
     */
    private function buildCategorySuggestion(array $cluster, Variasi $targetItem): ?array
    {
        $namaBarangSekarang = $targetItem->m_barang->nama_barang ?? '';
        if (stripos($namaBarangSekarang, 'belum dikategorikan') === false) {
            return null;
        }

        $parsed = $this->parser->parse($targetItem->nama_variasi);
        $generationIds = $parsed['generation_ids'];

        foreach ($cluster as $item) {
            if ($item->id_variasi === $targetItem->id_variasi) {
                continue;
            }
            $generationIds = array_merge($generationIds, $this->parser->parse($item->nama_variasi)['generation_ids']);
        }
        $generationIds = array_values(array_unique($generationIds));

        $generations = VehicleGeneration::with('vehicle')->whereIn('id', $generationIds)->get();

        // Cek apakah saran tipe_part cocok PERSIS (case-insensitive) dengan MBarang yang sudah
        // ada — kalau ketemu, dropdown & nama_barang final auto-terisi ke situ (bukan ke teks
        // hasil parsing mentah), supaya admin tidak perlu cari manual untuk kasus yang sudah jelas.
        $matchedMbarang = MBarang::whereRaw('LOWER(nama_barang) = ?', [mb_strtolower($parsed['tipe_part'])])->first();
        $namaBarangFinal = $matchedMbarang->nama_barang ?? $parsed['tipe_part'];

        // Pratinjau nama variasi PER ITEM (bukan cuma target) — supaya kalau beberapa item beda
        // merk dikategorikan bersamaan tanpa di-merge ("Kategorikan Saja"), masing-masing tetap
        // punya nama variasi sendiri: kata merk TIDAK ikut terhapus, beda dengan kata master
        // barang & kendaraan yang dihapus dari semua item secara seragam.
        $itemPreviews = [];
        foreach ($cluster as $item) {
            $itemPreviews[$item->id_variasi] = $this->parser->buildCleanNamaVariasi(
                $item->nama_variasi,
                $namaBarangFinal,
                $generationIds
            );
        }

        return [
            'tipe_part' => $namaBarangFinal,
            'matched_mbarang' => $matchedMbarang !== null,
            'part_number' => $parsed['part_number'],
            'generation_ids' => $generationIds,
            'generation_labels' => $generations->map(fn ($g) => [
                'id' => $g->id,
                'label' => ($g->vehicle->name ?? '?') . ' ' . $g->code . ($g->nickname ? " ({$g->nickname})" : ''),
            ])->values(),
            // Pratinjau awal per item — dihitung dari saran nama_barang & kendaraan di atas. Kalau
            // admin mengedit nama_barang/kendaraan di form, field ini TIDAK otomatis ikut berubah
            // (supaya tidak perlu duplikasi logic regex di JS) — makanya tetap bisa diedit manual,
            // nilai akhir yang disubmit itulah yang dipakai apa adanya.
            'item_previews' => $itemPreviews,
        ];
    }

    /**
     * Terapkan kategorisasi (MBarang + part_number + kompatibilitas kendaraan + bersihkan
     * nama_variasi) ke satu item target. Dipanggil SEBELUM merge di terapkanGrup() — kalau
     * dijalankan setelah merge, target sudah berubah stoknya tapi logikanya tetap sama saja;
     * urutan "kategorikan dulu, baru gabung" dipakai supaya hasil akhirnya konsisten dan mudah
     * ditelusuri di riwayat (kategorisasi selalu tercatat atas nama item yang masih "utuh").
     */
    private function applyCategorization(
        Variasi $target,
        string $namaBarang,
        ?string $partNumber,
        array $generationIds,
        ?string $namaVariasiOverride = null,
        ?string $tierOverride = null
    ): MBarang {
        $namaBarang = trim($namaBarang);

        $mbarang = MBarang::whereRaw('LOWER(nama_barang) = ?', [mb_strtolower($namaBarang)])->first();
        if (!$mbarang) {
            $mbarang = MBarang::create([
                'kode_barang' => $this->generateKodeBarang($namaBarang),
                'nama_barang' => $namaBarang,
                'id_kategori' => $target->m_barang->id_kategori ?? null,
                'is_active' => true,
            ]);
        }

        // Backup nama_variasi asli sebelum dibersihkan, supaya bisa ditrack/dikembalikan
        // kalau parsing otomatisnya keliru membuang sesuatu yang penting. Admin punya kontrol
        // penuh: kalau dia isi/edit field "nama_variasi_baru" di form, nilai itu yang dipakai
        // apa adanya — auto-cleanup cuma fallback kalau field itu dikosongkan.
        $namaVariasiLama = $target->nama_variasi;
        $namaVariasiBaru = trim((string) $namaVariasiOverride) !== ''
            ? trim($namaVariasiOverride)
            : $this->parser->buildCleanNamaVariasi($namaVariasiLama, $namaBarang, $generationIds);

        // Tier: deteksi dulu dari tag grade ('G'/'B'/'L'/tanpa-tag) di teks asli (SEBELUM
        // nama_variasi diubah, supaya tidak salah baca dari hasil edit manual yang mungkin sudah
        // tidak menyertakan tag-nya) — tapi kalau admin pilih tier manual di form (termasuk
        // sub-grade "Aftermarket A/B/C" yang memang tidak bisa dideteksi dari teks), itu yang
        // menang.
        if ($tierOverride !== null && $tierOverride !== '') {
            $target->tier = $tierOverride;
        } else {
            $this->service->applyGradeTier($target);
        }

        $target->id_barang = $mbarang->id_barang;
        $target->nama_variasi = $namaVariasiBaru;
        if (!empty($partNumber)) {
            $target->part_number = $partNumber;
        }
        $target->save();

        foreach ($generationIds as $genId) {
            ProductVariantCompatibility::firstOrCreate(
                ['id_variasi' => $target->id_variasi, 'vehicle_generation_id' => $genId],
                ['is_compatible' => true]
            );
        }

        if ($namaVariasiBaru !== $namaVariasiLama) {
            ItemCategorizationLog::create([
                'id_variasi' => $target->id_variasi,
                'barcode' => $target->barcode,
                'nama_variasi_lama' => $namaVariasiLama,
                'nama_variasi_baru' => $namaVariasiBaru,
                'id_barang_baru' => $mbarang->id_barang,
                'part_number_baru' => $target->part_number,
                'dikategorikan_oleh' => auth()->id(),
                'dikategorikan_at' => now(),
            ]);
        }

        return $mbarang;
    }

    private function generateKodeBarang(string $namaBarang): string
    {
        $words = preg_split('/\s+/', preg_replace('/[^A-Za-z0-9\s]/', '', $namaBarang));
        $code = mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 3), array_filter($words))));
        $code = mb_substr($code, 0, 10) ?: 'BRG';

        $base = $code;
        $suffix = 1;
        while (MBarang::where('kode_barang', $code)->exists()) {
            $suffixStr = (string) $suffix;
            $code = mb_substr($base, 0, 10 - mb_strlen($suffixStr)) . $suffixStr;
            $suffix++;
        }

        return $code;
    }

    /**
     * Satu aksi per grup, dua mode:
     * Kategorisasi SELALU diterapkan ke SEMUA item yang dicentang ("Ikut Diproses"), termasuk
     * target — bukan cuma target — supaya tiap item (per id_variasi) punya MBarang/nama_variasi/
     * tier yang benar dan tercatat di Riwayat Kategorisasi, baik dia nanti aktif sendiri maupun
     * berakhir dinonaktifkan karena merge (arsipnya tetap rapi, bukan numpuk di
     * "BELUM DIKATEGORIKAN"). Bedanya cuma di langkah SETELAH kategorisasi:
     * - 'merge': item selain target lalu digabungkan ke target (dinonaktifkan, stok &
     *   supplier dipindah) — tapi nama/tier hasil kategorisasi tadi tetap tersimpan di
     *   record-nya untuk arsip, sesuai keputusan: gak aktif tidak masalah asal datanya rapi.
     * - 'categorize_only': tidak ada merge sama sekali, semua tetap SKU aktif sendiri-sendiri —
     *   dipakai untuk item beda merk/kualitas yang memang tidak boleh disatukan jadi 1 SKU.
     */
    public function terapkanGrup(Request $request)
    {
        $validated = $request->validate([
            'target_id_variasi' => 'required|integer|exists:variasis,id_variasi',
            'merge_ids' => 'nullable|array',
            'merge_ids.*' => 'integer|exists:variasis,id_variasi',
            'mode' => 'required|in:merge,categorize_only',
            'nama_barang' => 'nullable|string|max:100',
            'nama_variasi_baru' => 'nullable|array',
            'nama_variasi_baru.*' => 'nullable|string|max:100',
            'tier_override' => 'nullable|array',
            'tier_override.*' => 'nullable|in:' . implode(',', DuplicateItemDetectionService::TIER_OPTIONS),
            'part_number' => 'nullable|string|max:255',
            'vehicle_generation_ids' => 'nullable|array',
            'vehicle_generation_ids.*' => 'integer|exists:vehicle_generations,id',
        ]);

        $target = Variasi::with('m_barang')->findOrFail($validated['target_id_variasi']);
        $mode = $validated['mode'];
        $selectedIds = array_values(array_unique(array_map('intval', $validated['merge_ids'] ?? [])));

        if (empty($validated['nama_barang']) && empty($selectedIds)) {
            return back()->with('error', 'Tidak ada aksi yang dijalankan — isi kategorisasi dan/atau pilih item lain.');
        }

        $actions = [];

        try {
            DB::transaction(function () use ($validated, $target, $mode, $selectedIds, &$actions) {
                if (!empty($validated['nama_barang'])) {
                    $idsToCategorize = array_values(array_unique(array_merge([$target->id_variasi], $selectedIds)));
                    $itemsToCategorize = Variasi::with('m_barang')->whereIn('id_variasi', $idsToCategorize)->get();

                    $mbarangNama = null;
                    foreach ($itemsToCategorize as $item) {
                        $override = $validated['nama_variasi_baru'][$item->id_variasi] ?? null;
                        $tierOverride = $validated['tier_override'][$item->id_variasi] ?? null;
                        $mbarang = $this->applyCategorization(
                            $item,
                            $validated['nama_barang'],
                            $validated['part_number'] ?? null,
                            $validated['vehicle_generation_ids'] ?? [],
                            $override,
                            $tierOverride
                        );
                        $mbarangNama = $mbarang->nama_barang;
                    }
                    $actions[] = count($idsToCategorize) . " item dikategorikan ke \"{$mbarangNama}\"";
                }

                if ($mode === 'merge') {
                    $mergeIds = array_values(array_diff($selectedIds, [$target->id_variasi]));
                    if (!empty($mergeIds)) {
                        $this->service->merge($target->id_variasi, $mergeIds, auth()->id());
                        $actions[] = 'item duplikat digabungkan';
                    }
                }
            });
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (empty($actions)) {
            return back()->with('error', 'Tidak ada aksi yang dijalankan.');
        }

        return back()->with('success', ucfirst(implode(' & ', $actions)) . " (target barcode {$target->barcode}).");
    }

    public function riwayat()
    {
        $merges = ItemDuplicateMerge::with(['target', 'merged', 'mergedByUser'])
            ->orderByDesc('merged_at')
            ->paginate(25);

        return view('pages.duplikat_item.riwayat', compact('merges'));
    }

    public function riwayatKategorisasi()
    {
        $logs = ItemCategorizationLog::with(['variasi', 'mbarang', 'dikategorikanOlehUser'])
            ->orderByDesc('dikategorikan_at')
            ->paginate(25);

        return view('pages.duplikat_item.riwayat_kategorisasi', compact('logs'));
    }
}
