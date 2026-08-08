<?php

namespace App\Http\Controllers\SupplierSelection;

use App\Http\Controllers\Controller;
use App\Models\SawKriteria;
use App\Models\SawNilaiHistorisDetail;
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

        // C1-C6 adalah kriteria inti — kode-nya dipakai langsung sebagai sumber
        // data spesifik di SawBatchCalculator (C1=harga inquiry, C2/C4/C5/C6=
        // kolom fixed saw_nilai_historis, C3=lead time). Menghapusnya akan
        // merusak pipeline kalkulasi, bukan cuma "menghilangkan kriteria".
        if (in_array($kriteria->kode, ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'], true)) {
            return redirect()->route('saw.kriteria.index')
                ->with('error', "Kriteria {$kriteria->kode} adalah kriteria inti sistem dan tidak bisa dihapus. "
                    . 'Nonaktifkan saja (ubah status) kalau ingin dikecualikan dari perhitungan.');
        }

        $sudahDipakai = SawNilaiHistorisDetail::where('kriteria_id', $kriteria->id)->exists();
        if ($sudahDipakai) {
            return redirect()->route('saw.kriteria.index')
                ->with('error', "Kriteria {$kriteria->kode} sudah memiliki data historis kinerja supplier yang terisi, "
                    . 'tidak bisa dihapus permanen. Nonaktifkan saja (ubah status) supaya data historisnya tetap tersimpan.');
        }

        $kriteria->delete();

        return redirect()->route('saw.kriteria.index')
            ->with('success', "Kriteria {$kriteria->kode} berhasil dihapus.");
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
