<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Supplier;
use App\Models\Penerimaan;
use Illuminate\Http\Request;
use App\Models\DetailPenerimaan;
use Yajra\DataTables\DataTables;

class PenerimaanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Penerimaan::with('supplier')->select('penerimaans.*');
            return DataTables::of($data)
                ->addColumn('supplier', fn($row) => $row->supplier->nama_supplier ?? '-')
                ->editColumn('Tanggal_Nota', fn($row) => \Carbon\Carbon::parse($row->Tanggal_Nota)->format('d-m-Y'))
                ->editColumn('Jatuh_Tempo', fn($row) => $row->Jatuh_Tempo ? \Carbon\Carbon::parse($row->Jatuh_Tempo)->format('d-m-Y') 
                : '-')

                ->editColumn('Grand_Total', fn($row) => 'Rp ' . number_format($row->Grand_Total, 0, ',', '.'))
                ->addColumn('status', function ($row) {
                    return $row->status === 'lunas' 
                        ? '<span class="badge bg-success">Lunas</span>'
                        : '<span class="badge bg-warning">Belum Lunas</span>';
                })
                ->addColumn('action', function($row) {
                return '
                    <button class="btn btn-sm btn-info view-btn" data-id="'.$row->ID_Penerimaan.'">
                        <i class="fas fa-eye"></i>
                    </button>';
            })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        
        return view('pages.penerimaan.index');
    }
    public function show($id)
    {
        $penerimaan = Penerimaan::with(['supplier', 'details.barang'])->findOrFail($id);

        return response()->json([
            'penerimaan' => $penerimaan,
            'details' => $penerimaan->details
        ]);
    }

    public function detailIndex(Request $request)
{
    if ($request->ajax()) {
        $data = DetailPenerimaan::with(['barang.supplier'])->select('detail_penerimaans.*')
                 ->orderByDesc('Tanggal'); 

        return DataTables::of($data)
            ->addColumn('no', fn($row) => '') // Akan diisi oleh DataTables numbering
            ->addColumn('Barcode', fn($row) => $row->barang->barcode ?? '-')
            ->addColumn('Nama_Barang', fn($row) => $row->barang->nama_barang ?? '-')
            ->addColumn('Supplier', fn($row) => $row->barang->supplier->nama_supplier ?? '-')
            ->editColumn('Jumlah', fn($row) => number_format($row->Jumlah, 0, ',', '.'))
            ->editColumn('Tanggal', fn($row) => \Carbon\Carbon::parse($row->Tanggal)->format('d-m-Y'))
            ->addColumn('action', function ($row) {
                return '<a href="#" class="btn btn-sm btn-info">Detail</a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    return view('pages.penerimaan.detail');
}


    public function create()
    {
        $suppliers = Supplier::all();
        $kategoris = Kategori::all();
        $units = Unit::all();
        $barangs = Barang::all();
        return view('pages.penerimaan.create', compact('suppliers', 'barangs', 'kategoris', 'units'));
    }

    public function store(Request $request)
{
    $request->validate([
        'Invoice' => 'required|string',
        'id_' => 'required|exists:suppliers,id_unit',
        'Tanggal_Nota' => 'required|date',
        'Tanggal_Datang' => 'required|date',
        'barang_details' => 'required|array|min:1',
        'barang_details.*.ID_Barang' => 'required|exists:barangs,ID_Barang',
        'barang_details.*.Jumlah' => 'required|numeric|min:1',
        'barang_details.*.Harga' => 'required|numeric|min:0',
    ]);

    // Hitung Total
    $total = 0;
    foreach ($request->barang_details as $detail) {
        $total += $detail['Jumlah'] * $detail['Harga'];
    }

    // Hitung PPN jika dipilih
    $includePPN = $request->has('include_ppn');
    $ppn = $includePPN ? $total * 0.11 : 0;
    $grand_total = $total + $ppn;

    // Tentukan jatuh tempo dan status
    if ($request->input('Metode_Pembayaran') == 'kredit') {
        $jatuhtempo = $request->input('Jatuh_Tempo');
        $statusPembayaran = "belum lunas";
    } else {
        $jatuhtempo = null;
        $statusPembayaran = "lunas";
    }

    // Simpan Penerimaan
    $penerimaan = Penerimaan::create([
        'Invoice' => $request->input('Invoice'),
        'id_unit' => $request->input('id_unit'),
        'Tanggal_Nota' => $request->input('Tanggal_Nota'),
        'Tanggal_Datang' => $request->input('Tanggal_Datang'),
        'Jatuh_Tempo' => $jatuhtempo,
        'Metode_Pembayaran' => $request->input('Metode_Pembayaran'),
        'status' => $statusPembayaran,
        'Total' => $total,
        'PPN' => $ppn,
        'Grand_Total' => $grand_total,
    ]);

    // Simpan detail barang
    foreach ($request->barang_details as $detail) {
        $subtotal = $detail['Jumlah'] * $detail['Harga'];

        DetailPenerimaan::create([
            'ID_Penerimaan' => $penerimaan->ID_Penerimaan,
            'ID_Barang' => $detail['ID_Barang'],
            'Jumlah' => $detail['Jumlah'],
            'Harga' => $detail['Harga'],
            'Total' => $subtotal,
            'Tanggal' => $request->input('Tanggal_Nota'),
            'Status' => 'disimpan'
        ]);

        // Update stok barang
        $barang = Barang::find($detail['ID_Barang']);
        $barang->stock += $detail['Jumlah'];
        $barang->save();
    }

    return redirect()->route('penerimaan.index')->with('success', 'Penerimaan berhasil disimpan');
}


    public function getBarangBySupplier($supplierId)
    {
        // Validasi supplier ID
        if (!is_numeric($supplierId)) {
            return response()->json([
                'error' => 'Supplier ID tidak valid'
            ], 400);
        }

        // Ambil data barang
        $barangs = Barang::where('id_unit', $supplierId)
                    ->select('ID_Barang', 'nama_barang')
                    ->get();

        return response()->json($barangs);
    }

    public function getBarangDatatable(Request $request, DataTables $dataTables)
{
    $query = Barang::with(['kategori', 'unit', 'supplier'])
        ->select('barangs.*');

    if ($request->supplier_id) {
        $query->where('id_unit', $request->supplier_id);
    }

    return $dataTables->eloquent($query)
        ->addColumn('action', function($barang) {
            return '';
        })
        ->rawColumns(['action'])
        ->toJson();
}


}