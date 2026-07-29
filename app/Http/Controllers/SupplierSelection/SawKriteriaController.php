<?php

namespace App\Http\Controllers\SupplierSelection;

use App\Http\Controllers\Controller;
use App\Models\SawKriteria;
use Illuminate\Http\Request;

class SawKriteriaController extends Controller
{
    public function index()
    {
        $kriterias = SawKriteria::orderBy('urutan')->orderBy('kode')->get();
        $totalBobotAktif = $kriterias->where('is_active', 1)->sum('bobot');

        return view('pages.procurement.saw_kriteria.index', compact('kriterias', 'totalBobotAktif'));
    }

    public function create()
    {
        $this->denyViewOnly();

        $nextKode = $this->generateNextKode();

        return view('pages.procurement.saw_kriteria.form', compact('nextKode'));
    }

    public function store(Request $request)
    {
        $this->denyViewOnly();

        $validated = $this->validateData($request);

        if ($error = $this->bobotTerlampaui($validated)) {
            return back()->withErrors(['bobot' => $error])->withInput();
        }

        // Kode dibuat otomatis oleh sistem (bukan input pengguna), sesuai
        // arahan pembimbing agar penomoran kriteria konsisten C1, C2, C3, ...
        $validated['kode'] = $this->generateNextKode();

        SawKriteria::create($validated);

        return redirect()->route('saw.kriteria.index')
            ->with('success', "Kriteria SAW {$validated['kode']} berhasil ditambahkan.");
    }

    public function edit(SawKriteria $kriteria)
    {
        $this->denyViewOnly();

        return view('pages.procurement.saw_kriteria.form', compact('kriteria'));
    }

    public function update(Request $request, SawKriteria $kriteria)
    {
        $this->denyViewOnly();

        $validated = $this->validateData($request, $kriteria);

        if ($error = $this->bobotTerlampaui($validated, $kriteria)) {
            return back()->withErrors(['bobot' => $error])->withInput();
        }

        $kriteria->update($validated);

        return redirect()->route('saw.kriteria.index')
            ->with('success', 'Kriteria SAW berhasil diperbarui.');
    }

    public function destroy(SawKriteria $kriteria)
    {
        $this->denyViewOnly();

        $kriteria->delete();

        return redirect()->route('saw.kriteria.index')
            ->with('success', "Kriteria {$kriteria->kode} berhasil dihapus.");
    }

    /**
     * Skalakan ulang bobot semua kriteria aktif secara proporsional supaya
     * totalnya tepat 1.0, dengan tiap bobot dibulatkan ke kelipatan 0.05 (5%)
     * agar angkanya rapi. Pakai largest-remainder method: bagi 1.0 jadi 20
     * "slot" 0.05, alokasikan slot bulat dulu, sisa slot diberikan ke
     * kriteria dengan sisa pecahan terbesar — supaya total tetap persis 1.0.
     */
    public function normalize()
    {
        $this->denyViewOnly();

        $aktif = SawKriteria::where('is_active', 1)->orderBy('urutan')->get();
        $total = $aktif->sum('bobot');

        if ($aktif->isEmpty() || $total <= 0) {
            return redirect()->route('saw.kriteria.index')
                ->with('error', 'Tidak ada kriteria aktif dengan bobot > 0 untuk dinormalisasi.');
        }

        $totalSlot = 20; // 20 x 0.05 = 1.0
        $slotMentah = $aktif->map(fn ($k) => ((float) $k->bobot) / $total * $totalSlot);
        $slotDasar  = $slotMentah->map(fn ($v) => (int) floor($v));
        $sisaSlot   = $totalSlot - $slotDasar->sum();

        $urutanSisa = $slotMentah->keys()
            ->sortByDesc(fn ($i) => $slotMentah[$i] - $slotDasar[$i])
            ->values();

        $slotFinal = $slotDasar;
        for ($i = 0; $i < $sisaSlot; $i++) {
            $idx = $urutanSisa[$i];
            $slotFinal[$idx] = $slotFinal[$idx] + 1;
        }

        $aktif->values()->each(function ($k, $i) use ($slotFinal) {
            $k->update(['bobot' => round($slotFinal[$i] * 0.05, 2)]);
        });

        return redirect()->route('saw.kriteria.index')
            ->with('success', 'Bobot kriteria aktif berhasil dinormalisasi ke kelipatan 5%, total kini 100%.');
    }

    /**
     * Cek apakah bobot yang diinput akan membuat total bobot kriteria AKTIF
     * melebihi 100%. Kriteria yang dinonaktifkan tidak ikut dihitung.
     * Mengembalikan pesan error jika melebihi, atau null jika aman.
     */
    private function bobotTerlampaui(array $validated, ?SawKriteria $kriteria = null): ?string
    {
        if (!$validated['is_active']) {
            return null;
        }

        $totalLain = SawKriteria::where('is_active', 1)
            ->when($kriteria, fn ($q) => $q->where('id', '!=', $kriteria->id))
            ->sum('bobot');

        $totalBaru = $totalLain + $validated['bobot'];

        if ($totalBaru > 1.0001) {
            $sisaTersedia = max(0, round(1 - $totalLain, 4));

            return sprintf(
                'Bobot %.2f%% membuat total kriteria aktif menjadi %.2f%% (melebihi 100%%). '
                . 'Sisa bobot yang tersedia untuk kriteria ini: %.2f%%.',
                $validated['bobot'] * 100,
                $totalBaru * 100,
                $sisaTersedia * 100
            );
        }

        return null;
    }

    private function validateData(Request $request, ?SawKriteria $kriteria = null): array
    {
        $validated = $request->validate([
            'nama'   => 'required|string|max:100',
            'jenis'  => 'required|in:cost,benefit',
            'bobot'  => 'required|numeric|min:0|max:1',
            'satuan' => 'nullable|string|max:30',
            'urutan' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    /**
     * Kriteria & Bobot hanya boleh diubah oleh Manager Toko (role 'supervisor')
     * atau admin/owner. Bagian Pembelian (role 'procurement') cuma boleh melihat.
     */
    private function denyViewOnly(): void
    {
        if (!in_array(auth()->user()->role, ['owner', 'admin', 'supervisor'], true)) {
            abort(403, 'Kriteria & Bobot hanya dapat diubah oleh Manager Toko.');
        }
    }

    /**
     * Kode kriteria berikutnya, format C<n> — dibuat otomatis dari kode
     * tertinggi yang sudah ada (kode lama seperti "satuan" custom di luar
     * pola C<n> diabaikan dari perhitungan, bukan penyebab error).
     */
    private function generateNextKode(): string
    {
        $maxNumber = SawKriteria::pluck('kode')
            ->map(fn ($kode) => preg_match('/^C(\d+)$/', $kode, $m) ? (int) $m[1] : 0)
            ->max();

        return 'C' . ($maxNumber + 1);
    }
}
