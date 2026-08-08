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
            'rincian_kriteria' => $d->rincian_kriteria,
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
