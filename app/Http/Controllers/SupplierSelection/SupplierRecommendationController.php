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
        $needlists = Needlist::with('user')
            ->where('status', 'selection_in_progress')
            ->latest()
            ->get();

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

        // Kelompok yang sudah pernah dihitung sebelumnya — dilewati supaya
        // tidak dihitung ulang tiap kali halaman dibuka (hanya "Hitung Ulang"
        // manual yang memaksa hitung ulang semua).
        $alreadyComputedTierKeys = SawPerhitungan::where('needlist_id', $needlist->id)
            ->whereNotNull('tier_key')
            ->pluck('tier_key')
            ->all();

        // Otomatis hitung kelompok yang BELUM PERNAH dihitung sama sekali, supaya
        // tombol "Pilih" tidak terkunci menunggu user klik tombol rekomendasi
        // dahulu — sesuai alur: begitu syarat >1 supplier terpenuhi, sistem yang
        // menjalankan rekomendasi, bukan menunggu permintaan eksplisit dari user.
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
            'supplierIdsWithHistoris'
        ));
    }
}
