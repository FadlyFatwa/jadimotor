<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Variasi;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\PenjualanDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PenjualanController extends Controller
{
    public function index()
    {
        $pelanggans = Pelanggan::all();
        $barangs = Variasi::all();
        $cartItems = Cart::with('variasi')->where('user_id', Auth::id())->get();
        
        $lastInvoiceNumber = Penjualan::whereDate('tanggal', today())
            ->count();
            
        return view('pages.penjualan.create', compact('pelanggans', 'barangs', 'cartItems', 'lastInvoiceNumber'));
    }

    public function getCart()
    {
        $cartItems = Cart::with('variasi')->where('user_id', Auth::id())->get();
        return view('pages.penjualan.cart', compact('cartItems'))->render();
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'barcode' => 'sometimes|required',
            'id_variasi' => 'sometimes|required',
            'qty' => 'required|integer|min:1',
            'nama_barang_jual' => 'sometimes|required',
            'harga' => 'sometimes|required|numeric',
            'diskon' => 'sometimes|numeric|min:0'
        ]);

        if ($request->has('barcode')) {
            $barang = Variasi::where('barcode', $request->barcode)->first();

            if (!$barang) {
                return response()->json(['message' => 'variasi tidak ditemukan'], 404);
            }
            $request->merge([
                'id_variasi' => $barang->id_variasi,
                'nama_barang_jual' => $barang->nama_barang,
                'harga' => $barang->harga,
                'diskon' => $request->diskon ?? 0
            ]);
        }

        $barang = Variasi::find($request->id_variasi);

        if (!$barang) {
            return response()->json(['message' => 'variasi tidak ditemukan'], 404);
        }

        if ($barang->stock < $request->qty) {
            return response()->json(['message' => 'Stok tidak mencukupi'], 400);
        }

        $existingCart = Cart::where('user_id', Auth::id())
            ->where('id_variasi', $request->id_variasi)
            ->first();

        if ($existingCart) {
            $newQty = $existingCart->qty + $request->qty;
            if ($newQty > $barang->stock) {
                return response()->json(['message' => 'Stok tidak mencukupi untuk jumlah yang diminta'], 400);
            }
            $existingCart->update([
                'qty' => $newQty,
                'diskon' => $request->diskon ?? $existingCart->diskon,
                'harga' => $request->harga ?? $existingCart->harga
            ]);
            $existingCart->calculateSubtotal();
        } else {
            $cart = Cart::create([
                'user_id' => Auth::id(),
                'id_variasi' => $request->id_variasi,
                'nama_barang_jual' => $request->nama_barang_jual,
                'harga' => $request->harga,
                'diskon' => $request->diskon ?? 0,
                'qty' => $request->qty,
                'subtotal' => ($request->harga - ($request->diskon ?? 0)) * $request->qty
            ]);
        }

        return response()->json(['message' => 'variasi berhasil ditambahkan ke keranjang']);
    }

    public function updateCart(Request $request, Cart $cart)
{
    // Validasi kepemilikan cart
    if ($cart->user_id !== Auth::id()) {
        return response()->json([
            'success' => false,
            'message' => 'Anda tidak memiliki akses ke keranjang ini'
        ], 403);
    }

    $request->validate([
        'harga' => 'required|numeric|min:1',
        'diskon' => 'required|numeric|min:0',
        'qty' => 'required|integer|min:1'
    ]);

    // Validasi stok
    $barang = $cart->barang;
    if ($request->qty > $barang->stock) {
        return response()->json([
            'success' => false,
            'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $barang->stock
        ], 400);
    }

    // Update cart
    $cart->update([
        'harga' => $request->harga,
        'diskon' => $request->diskon,
        'qty' => $request->qty,
        'subtotal' => ($request->harga - $request->diskon) * $request->qty
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Item keranjang berhasil diperbarui'
    ]);
}

    public function removeFromCart(Cart $cart)
{
    // Validasi kepemilikan
    if ($cart->user_id !== Auth::id()) {
        abort(403, 'Unauthorized action.');
    }
    
    $cart->delete();
    
    return response()->json([
        'success' => true,
        'message' => 'Item berhasil dihapus dari keranjang'
    ]);
}

    public function clearCart()
    {
        Cart::where('user_id', Auth::id())->delete();
        return response()->json(['message' => 'Keranjang dibersihkan']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice' => 'required',
            'tanggal' => 'required|date',
            'metode_pembayaran' => 'required|in:cash,transfer,kredit',
            'cash_bayar' => 'required_if:metode_pembayaran,cash|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0|max:100'
        ]);

        DB::beginTransaction();
        try {
            $cartItems = Cart::with('variasi')->where('user_id', Auth::id())->get();

            if ($cartItems->isEmpty()) {
                throw new \Exception("Keranjang belanja kosong");
            }

            $penjualan = Penjualan::create([
                'nomor_nota' => $request->invoice,
                'tanggal' => $request->tanggal,
                'pelanggan_id' => $request->pelanggan_id,
                'user_id' => Auth::id(),
                'diskon' => $request->diskon ?? 0,
                'metode_pembayaran' => $request->metode_pembayaran,
                'total' => 0,
                'grand_total' => 0,
                'status' => 'completed',
            ]);

            $total = 0;

            foreach ($cartItems as $item) {
                $barang = $item->barang;
                
                if ($barang->stock < $item->qty) {
                    throw new \Exception("Stok tidak mencukupi untuk barang: " . $barang->nama_barang);
                }

                $subtotal = $item->harga * $item->qty;
                $total += $subtotal;

                PenjualanDetail::create([
                    'id_penjualan' => $penjualan->id_penjualan,
                    'id_variasi' => $item->id_variasi,
                    'nama_barang_jual' => $item->nama_barang_jual,
                    'harga' => $item->harga,
                    'qty' => $item->qty,
                    'subtotal' => $subtotal
                ]);

                $barang->decrement('stock', $item->qty);
            }

            $grandTotal = $total - ($total * ($request->diskon ?? 0) / 100);

            if ($request->metode_pembayaran == 'cash' && $request->cash_bayar < $grandTotal) {
                throw new \Exception("Pembayaran cash kurang dari total yang harus dibayar");
            }

            $penjualan->update(['total' => $total, 'grand_total' => $grandTotal]);

            Cart::where('user_id', Auth::id())->delete();

            DB::commit();

            return redirect()->route('penjualan.index')
                ->with('success', 'Transaksi penjualan berhasil disimpan! Invoice: ' . $request->invoice);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan penjualan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $penjualan = Penjualan::with(['pelanggan', 'details.barang', 'user'])
            ->findOrFail($id);
            
        return view('pages.penjualan.show', compact('penjualan'));
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $penjualan = Penjualan::with('details')->findOrFail($id);

            foreach ($penjualan->details as $detail) {
                variasi::where('id_variasi', $detail->id_variasi)
                    ->increment('stock', $detail->qty);
            }

            $penjualan->details()->delete();
            $penjualan->delete();

            DB::commit();

            return redirect()->route('penjualan.index')
                ->with('success', 'Data penjualan berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal menghapus penjualan: ' . $e->getMessage());
        }
    }
}