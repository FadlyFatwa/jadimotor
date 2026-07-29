<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Barang;
use App\Models\MBarang;
use App\Models\Kategori;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class MasterBarangController extends Controller
{
    /**
     * Menampilkan data master barang dengan DataTables.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = MBarang::with('kategori')->select('m_barangs.*');
            
            return DataTables::of($data)
                ->addColumn('kategori', function($row) {
                    return $row->kategori->nama_kategori ?? '-';
                })
                ->addColumn('is_active', function($row) {
                    return $row->is_active
                        ? '<span class="badge badge-success">Aktif</span>'
                        : '<span class="badge badge-secondary">Nonaktif</span>';
                })
                ->addColumn('action', function($row) {
                    return '<a href="'.route('m_barang.edit', $row->id_barang).'" class="btn btn-warning btn-sm">Edit</a>
                            <form action="'.route('m_barang.destroy', $row->id_barang).'" method="POST" style="display:inline">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Yakin hapus?\')">Hapus</button>
                            </form>';
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return view('pages.mbarang.index');
    }

    /**
     * Menampilkan form untuk membuat master barang baru.
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $kategoris = Kategori::all();
        return view('pages.mbarang.form', compact('kategoris'));
    }

    /**
     * Menyimpan master barang baru.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:255',
            'nama_barang' => 'required|string|max:255',
            'id_kategori' => 'required|exists:kategoris,id_kategori',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $m_barang = MBarang::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'id_barang'   => $m_barang->id_barang,
                'nama_barang' => $m_barang->nama_barang,
            ], 201);
        }

        return redirect()->route('m_barang.index')->with('success', 'Master barang berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit master barang.
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $m_barang = MBarang::findOrFail($id);
        $kategoris = Kategori::all();
        return view('pages.mbarang.form', compact('m_barang', 'kategoris'));
    }

    /**
     * Memperbarui master barang.
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $m_barang = MBarang::findOrFail($id);
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:255',
            'nama_barang' => 'required|string|max:255',
            'id_kategori' => 'required|exists:kategoris,id_kategori',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $m_barang->update($validated);
        return redirect()->route('m_barang.index')->with('success', 'Master barang berhasil diperbarui.');
    }

    /**
     * Menghapus master barang.
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $m_barang = MBarang::findOrFail($id);
        $m_barang->delete();
        return redirect()->route('m_barang.index')->with('success', 'Master barang berhasil dihapus.');
    }
}