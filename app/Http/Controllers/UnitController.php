<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::all();
        return view('pages.unit.index', compact('units'));
    }

    public function create()
    {
        return view('pages.unit.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_unit' => 'required|unique:units|max:10',
            'nama_unit' => 'required|max:10',
        ]);

        Unit::create($validated);
        return redirect()->route('unit.index')->with('success', 'Unit berhasil ditambahkan');
    }

    public function edit(Unit $unit)
    {
        return view('pages.unit.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'kode_unit' => 'required|max:10|unique:units,kode_unit,'.$unit->id_unit.',id_unit',
            'nama_unit' => 'required|max:10',
        ]);

        $unit->update($validated);
        return redirect()->route('unit.index')->with('success', 'Unit berhasil diupdate');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return redirect()->route('unit.index')->with('success', 'Unit berhasil dihapus');
    }
}
