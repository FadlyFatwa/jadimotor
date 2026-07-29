<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::withCount('generations')->latest()->get();
        return view('pages.kendaraan.index', compact('vehicles'));
    }

    public function create()
    {
        return view('pages.kendaraan.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
        ]);

        Vehicle::create($validated);
        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function edit(Vehicle $kendaraan)
    {
        return view('pages.kendaraan.form', ['vehicle' => $kendaraan]);
    }

    public function update(Request $request, Vehicle $kendaraan)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
        ]);

        $kendaraan->update($validated);
        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(Vehicle $kendaraan)
    {
        $kendaraan->delete();
        return redirect()->route('kendaraan.index')->with('success', 'Kendaraan berhasil dihapus.');
    }
}
