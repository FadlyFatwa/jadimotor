<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Needlist;
use App\Models\SawPerhitungan;
use App\Models\SawRekomendasi;
use App\Services\SawBatchCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierSelectionSawController extends Controller
{
    public function __construct(
        private SawBatchCalculator $batchCalculator,
    ) {}

    // =========================================================================
    // PUBLIC: Hitung SAW untuk SEMUA kelompok dalam satu needlist — endpoint AJAX
    // =========================================================================

    /**
     * POST /procurement/pemilihan-supplier/{needlist}/rekomendasi-semua
     * Hitung SAW untuk seluruh kelompok (master barang x cluster kendaraan x tier)
     * dalam satu needlist yang punya minimal 2 supplier, dalam satu request.
     */
    public function hitungSemua(Request $request, $needlist)
    {
        $needlistId = (int) $needlist;

        $needlist = Needlist::with([
            'details',
            'supplierInquiries.supplier',
            'supplierInquiries.items.variasi.m_barang',
            'supplierInquiries.items.variasi.vehicleGenerations.vehicle',
        ])->findOrFail($needlistId);

        // Tombol ini = "Hitung Ulang" eksplisit, jadi paksa hitung ulang SEMUA
        // kelompok (>=2 supplier), termasuk yang sudah pernah dihitung sebelumnya.
        try {
            $results = $this->batchCalculator->calculateForNeedlist($needlist);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if (empty($results)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada kelompok dengan minimal 2 supplier yang bisa dihitung.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }

    // =========================================================================
    // PUBLIC: Detail Perhitungan SAW (untuk modal laporan)
    // =========================================================================

    /**
     * GET /procurement/supplier-selection/detail-saw/{id}
     * Kembalikan JSON detail satu perhitungan untuk ditampilkan di modal.
     */
    public function detailSaw(int $id)
    {
        $perhitungan = SawPerhitungan::with(['details.supplier', 'details.variasi', 'variasi.m_barang', 'needlist'])
            ->findOrFail($id);

        $details = $perhitungan->details->sortBy('ranking')->map(fn ($d) => [
            'supplier_id' => $d->supplier_id,
            'supplier'   => $d->supplier->nama_supplier ?? '-',
            'id_variasi'   => $d->id_variasi,
            'nama_variasi' => $d->variasi->nama_variasi ?? null,
            'nilai_c1'   => $d->nilai_c1,  'norm_c1'   => $d->norm_c1,  'weighted_c1' => $d->weighted_c1,
            'nilai_c2'   => $d->nilai_c2,  'norm_c2'   => $d->norm_c2,  'weighted_c2' => $d->weighted_c2,
            'nilai_c3'   => $d->nilai_c3,  'norm_c3'   => $d->norm_c3,  'weighted_c3' => $d->weighted_c3,
            'nilai_c4'   => $d->nilai_c4,  'norm_c4'   => $d->norm_c4,  'weighted_c4' => $d->weighted_c4,
            'nilai_c5'   => $d->nilai_c5,  'norm_c5'   => $d->norm_c5,  'weighted_c5' => $d->weighted_c5,
            'nilai_c6'   => $d->nilai_c6,  'norm_c6'   => $d->norm_c6,  'weighted_c6' => $d->weighted_c6,
            'nilai_vi'   => $d->nilai_vi,
            'ranking'    => $d->ranking,
            'is_recommended' => $d->is_recommended,
        ])->values();

        return response()->json([
            'success'       => true,
            'perhitungan'   => [
                'id'           => $perhitungan->id,
                'kode_needlist'=> $perhitungan->needlist->kode_needlist ?? '-',
                'variasi'      => $perhitungan->variasi->nama_variasi ?? '-',
                'calculated_at'=> $perhitungan->calculated_at?->format('d/m/Y H:i'),
            ],
            'bobot_snapshot' => $perhitungan->bobot_snapshot,
            'details'        => $details,
        ]);
    }

    // =========================================================================
    // PUBLIC: Laporan SAW
    // =========================================================================

    /**
     * GET /procurement/supplier-selection/laporan
     * Rekap semua perhitungan SAW dan keputusan user.
     */
    public function laporan(Request $request)
    {
        $query = SawPerhitungan::with([
            'needlist',
            'variasi.m_barang',
            'mBarang',
            'details.supplier',
            'rekomendasi.supplierSaw',
            'rekomendasi.supplierDipilih',
            'rekomendasi.variasi.vehicleGenerations.vehicle',
        ])->latest('calculated_at');

        if ($request->filled('needlist_id')) {
            $query->where('needlist_id', $request->needlist_id);
        }

        $perhitungans = $query->paginate(20)->withQueryString();
        $needlists    = Needlist::orderByDesc('created_at')->get();

        // Statistik ringkas
        $totalHitung      = SawPerhitungan::count();
        $totalKonfirmasi  = SawRekomendasi::count();
        $totalIkuti       = SawRekomendasi::where('mengikuti_rekomendasi', 1)->count();
        $totalOverride    = SawRekomendasi::where('mengikuti_rekomendasi', 0)->count();
        // Total pemilihan aktual dari inquiry (status = selected)
        $totalPilihan     = DB::table('supplier_inquiry_items')
            ->where('status', 'selected')->count();

        return view('pages.procurement.saw_laporan.index', compact(
            'perhitungans', 'needlists',
            'totalHitung', 'totalKonfirmasi', 'totalIkuti', 'totalOverride', 'totalPilihan'
        ));
    }

}
