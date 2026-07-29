<?php
namespace App\Http\Controllers\Procurement;

use App\Models\Variasi;
use App\Models\CartNeedlist;
use Illuminate\Http\Request;
use App\Models\SupplierVariasi;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CartNeedlistController extends Controller
{
    public function index()
    {
        $cartItems = CartNeedlist::with('variasi.m_barang')
            ->where('user_id', Auth::id())
            ->get();

        return view('pages.procurement.cart.index', compact('cartItems'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_variasi' => 'required|exists:variasis,id_variasi',
            'qty' => 'required|numeric|min:1'
        ]);

        $existing = CartNeedlist::where('user_id', Auth::id())
            ->where('id_variasi', $request->id_variasi)
            ->first();

        if ($existing) {
            $existing->qty += $request->qty;
            $existing->save();
        } else {
            CartNeedlist::create([
                'user_id' => Auth::id(),
                'id_variasi' => $request->id_variasi,
                'qty' => $request->qty
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $item = CartNeedlist::findOrFail($id);
        if ($item->user_id == Auth::id()) {
            $item->delete();
        }

        return response()->json(['status' => 'deleted']);
    }

    public function ajaxBarangVariasi()
    {
        $data = SupplierVariasi::with(['variasi.m_barang', 'supplier']);

        return datatables()->of($data)
            ->addColumn('barcode', function ($row) {
                return $row->variasi->barcode ?? '-';
            })
            ->addColumn('nama_barang', function ($row) {
                return $row->variasi->m_barang->nama_barang ?? '-';
            })
            ->addColumn('nama_variasi', function ($row) {
                return $row->variasi->nama_variasi ?? '-';
            })
            ->addColumn('nama_supplier', function ($row) {
                return $row->supplier->nama_supplier ?? '-';
            })
            ->addColumn('harga_beli_format', function ($row) {
                return 'Rp ' . number_format($row->harga_beli, 0, ',', '.');
            })
            ->addColumn('stock', function ($row) {
                return $row->variasi->stock ?? 0;
            })
            ->addColumn('action', function ($row) {
                return '<button class="btn btn-sm btn-success btn-pilih-barang"
                    data-id="'.$row->id_variasi.'"
                    data-barcode="'.$row->variasi->barcode.'"
                    data-nama="'.$row->variasi->nama_variasi.'"
                    data-nama_barang="'.$row->variasi->m_barang->nama_barang.'"
                    data-supplier="'.$row->supplier->nama_supplier.'"
                    >Pilih</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

}
