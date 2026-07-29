<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetailPenerimaan;
use Yajra\DataTables\DataTables;

class DetailPenerimaanController extends Controller
{
    public function detailIndex(Request $request)
{
    if ($request->ajax()) {
        $data = DetailPenerimaan::with(['barang.supplier'])->select('detail_penerimaans.*');

        return DataTables::of($data)
            ->addColumn('no', fn($row) => '') // Akan diisi oleh DataTables numbering
            ->addColumn('Barcode', fn($row) => $row->barang->Barcode ?? '-')
            ->addColumn('Nama_Barang', fn($row) => $row->barang->nama_barang ?? '-')
            ->addColumn('Supplier', fn($row) => $row->barang->supplier->Nama_Supplier ?? '-')
            ->editColumn('Jumlah', fn($row) => number_format($row->Jumlah, 0, ',', '.'))
            ->editColumn('Total', fn($row) => 'Rp ' . number_format($row->Total, 2, ',', '.'))
            ->editColumn('Tanggal', fn($row) => \Carbon\Carbon::parse($row->Tanggal)->format('d-m-Y'))
            ->addColumn('action', function ($row) {
                return '<a href="#" class="btn btn-sm btn-info">Detail</a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    return view('pages.detailpenerimaan.index');
}
}
