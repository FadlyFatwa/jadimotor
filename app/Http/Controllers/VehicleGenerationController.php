<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleGeneration;
use Illuminate\Http\Request;

class VehicleGenerationController extends Controller
{
    public function index(Vehicle $kendaraan)
    {
        $generations = $kendaraan->generations()->latest()->get();
        return view('pages.kendaraan.generasi.index', compact('kendaraan', 'generations'));
    }

    public function create(Vehicle $kendaraan)
    {
        return view('pages.kendaraan.generasi.form', compact('kendaraan'));
    }

    public function store(Request $request, Vehicle $kendaraan)
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:50',
            'nickname'   => 'nullable|string|max:100',
            'start_year' => 'nullable|integer|min:1900|max:2100',
            'end_year'   => 'nullable|integer|min:1900|max:2100|gte:start_year',
        ]);

        $kendaraan->generations()->create($validated);
        return redirect()->route('kendaraan.generasi.index', $kendaraan)->with('success', 'Generasi berhasil ditambahkan.');
    }

    public function edit(Vehicle $kendaraan, VehicleGeneration $generasi)
    {
        abort_unless($generasi->vehicle_id === $kendaraan->id, 404);

        return view('pages.kendaraan.generasi.form', compact('kendaraan', 'generasi'));
    }

    public function update(Request $request, Vehicle $kendaraan, VehicleGeneration $generasi)
    {
        abort_unless($generasi->vehicle_id === $kendaraan->id, 404);

        $validated = $request->validate([
            'code'       => 'required|string|max:50',
            'nickname'   => 'nullable|string|max:100',
            'start_year' => 'nullable|integer|min:1900|max:2100',
            'end_year'   => 'nullable|integer|min:1900|max:2100|gte:start_year',
        ]);

        $generasi->update($validated);
        return redirect()->route('kendaraan.generasi.index', $kendaraan)->with('success', 'Generasi berhasil diperbarui.');
    }

    public function destroy(Vehicle $kendaraan, VehicleGeneration $generasi)
    {
        abort_unless($generasi->vehicle_id === $kendaraan->id, 404);

        $generasi->delete();
        return redirect()->route('kendaraan.generasi.index', $kendaraan)->with('success', 'Generasi berhasil dihapus.');
    }
}
