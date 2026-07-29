<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::all();
        return view('pages.supplier.index', compact('suppliers'));
    }

    public function create()
    {
        return view('pages.supplier.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_supplier' => 'required|unique:suppliers|max:10',
            'nama_supplier' => 'required|max:100',
            'no_telp' => 'required|max:15',
            'alamat' => 'required|max:50',
        ]);

        Supplier::create($validated);
        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil ditambahkan');
    }

    public function edit(Supplier $supplier)
    {
        return view('pages.supplier.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'kode_supplier' => 'required|max:10|unique:suppliers,kode_supplier,'.$supplier->id_supplier.',id_supplier',
            'nama_supplier' => 'required|max:100',
            'no_telp' => 'required|max:15',
            'alamat' => 'required|max:50',
        ]);

        $supplier->update($validated);
        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil diupdate');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil dihapus');
    }
}
