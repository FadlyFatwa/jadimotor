<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;

class KategoriController extends Controller
{
    public function index()
    {
        $kategoris = Kategori::all();
        return view('pages.kategori.index', compact('kategoris'));
    }

    public function create()
    {
        return view('pages.kategori.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_kategori' => 'required|unique:kategoris|max:10',
            'nama_kategori' => 'required|max:100',
            'slug'          => 'nullable|unique:kategoris|max:100',
            'description'   => 'nullable|string',
        ]);

        Kategori::create($validated);
        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil ditambahkan');
    }

    public function edit(Kategori $kategori)
    {
        return view('pages.kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $validated = $request->validate([
            'kode_kategori' => 'required|max:10|unique:kategoris,kode_kategori,'.$kategori->id_kategori.',id_kategori',
            'nama_kategori' => 'required|max:100',
            'slug'          => 'nullable|max:100|unique:kategoris,slug,'.$kategori->id_kategori.',id_kategori',
            'description'   => 'nullable|string',
        ]);

        $kategori->update($validated);
        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(Kategori $kategori)
    {
        $kategori->delete();
        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil dihapus');
    }
}
