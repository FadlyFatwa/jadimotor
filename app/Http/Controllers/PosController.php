<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Barang;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\PenjualanDetail;

class PosController extends Controller
{
    // PosController.php
        public function index()
        {
            $barangs = Barang::all(); // untuk modal pilih barang
            $cart = Cart::where('user_id', auth()->id())->get();
            return view('pos.index', compact('barangs', 'cart'));
        }

        public function addToCart(Request $request)
        {
            $barang = Barang::findOrFail($request->barang_id);

            Cart::create([
                'user_id' => auth()->id(),
                'barang_id' => $barang->id,
                'nama_barang_jual' => $request->nama_barang_jual ?? $barang->nama,
                'harga' => $request->harga ?? $barang->harga_jual,
                'qty' => $request->qty,
                'subtotal' => ($request->harga ?? $barang->harga_jual) * $request->qty
            ]);

            return redirect()->route('pos.index')->with('success', 'Barang ditambahkan ke cart');
        }

        public function updateCart(Request $request, Cart $cart)
        {
            $cart->update([
                'qty' => $request->qty,
                'harga' => $request->harga,
                'nama_barang_jual' => $request->nama_barang_jual,
                'subtotal' => $request->harga * $request->qty,
            ]);

            return back()->with('success', 'Cart diperbarui');
        }

        public function deleteCart(Cart $cart)
        {
            $cart->delete();
            return back()->with('success', 'Barang dihapus dari cart');
        }

        public function checkout()
        {
            $cartItems = Cart::where('user_id', auth()->id())->get();

            if ($cartItems->isEmpty()) return back()->with('error', 'Cart kosong');

            $penjualan = Penjualan::create([
                'nomor_nota' => 'PNJ' . now()->format('YmdHis'),
                'tanggal' => now(),
                'id_pelanggan' => 1, // default / bisa pilih
                'total' => $cartItems->sum('subtotal'),
                'grand_total' => $cartItems->sum('subtotal'),
                'metode_pembayaran' => 'Cash',
                'status' => 'Lunas'
            ]);

            foreach ($cartItems as $item) {
                PenjualanDetail::create([
                    'id_penjualan' => $penjualan->id,
                    'id_barang' => $item->barang_id,
                    'nama_barang_jual' => $item->nama_barang_jual,
                    'harga' => $item->harga,
                    'qty' => $item->qty,
                    'subtotal' => $item->subtotal,
                ]);

                // Update stok
                $item->barang->decrement('stok', $item->qty);
            }

            // Hapus cart
            Cart::where('user_id', auth()->id())->delete();

            return redirect()->route('pos.index')->with('success', 'Transaksi berhasil disimpan');
        }

}
