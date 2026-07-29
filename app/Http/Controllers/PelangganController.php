<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    /**
     * Menampilkan daftar pelanggan.
     */
    public function index()
    {
        $pelanggans = Pelanggan::latest()->paginate(10);
        return view('pelanggan.index', compact('pelanggans'));
    }

    /**
     * Menampilkan form tambah pelanggan.
     */
    public function create()
    {
        return view('pelanggan.form');
    }

    /**
     * Simpan data pelanggan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|unique:pelanggans,email',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:500',
        ]);

        Pelanggan::create($request->all());

        return redirect()->route('pelanggan.index')
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit pelanggan.
     */
    public function edit($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        return view('pelanggan.form', compact('pelanggan'));
    }

    /**
     * Update data pelanggan.
     */
    public function update(Request $request, $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|unique:pelanggans,email,' . $pelanggan->id,
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:500',
        ]);

        $pelanggan->update($request->all());

        return redirect()->route('pelanggan.index')
            ->with('success', 'Pelanggan berhasil diperbarui.');
    }

    /**
     * Hapus data pelanggan.
     */
    public function destroy($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        $pelanggan->delete();

        return redirect()->route('pelanggan.index')
            ->with('success', 'Pelanggan berhasil dihapus.');
    }
}
