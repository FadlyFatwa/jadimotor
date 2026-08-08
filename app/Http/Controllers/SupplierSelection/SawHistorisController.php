<?php

namespace App\Http\Controllers\SupplierSelection;

use App\Http\Controllers\Controller;
use App\Models\SawKriteria;
use App\Models\SawNilaiHistoris;
use App\Models\SawNilaiHistorisDetail;
use App\Models\Supplier;
use App\Services\SupplierPerformanceService;
use Illuminate\Http\Request;

class SawHistorisController extends Controller
{
    public function index(Request $request)
    {
        $query = SawNilaiHistoris::with(['supplier', 'details.kriteria'])
            ->latest('periode_akhir');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $historis      = $query->paginate(20)->withQueryString();
        $suppliers     = Supplier::orderBy('nama_supplier')->get();
        $kriteriaCustom = $this->kriteriaCustomAktif();

        return view('pages.procurement.saw_historis.index', compact('historis', 'suppliers', 'kriteriaCustom'));
    }

    public function create()
    {
        $this->denyViewOnly();

        $suppliers      = Supplier::orderBy('nama_supplier')->get();
        $kriteriaDinamis = $this->kriteriaDinamisAktif();

        return view('pages.procurement.saw_historis.form', compact('suppliers', 'kriteriaDinamis'));
    }

    public function store(Request $request)
    {
        $this->denyViewOnly();

        $this->normalizeDecimalInputs($request);

        $validated = $request->validate(array_merge([
            'supplier_id'      => 'required|exists:suppliers,id_supplier',
            'periode_mulai'    => 'required|date',
            'periode_akhir'    => 'required|date|after_or_equal:periode_mulai',
            'jumlah_transaksi' => 'nullable|integer|min:0',
            'catatan'          => 'nullable|string|max:500',
        ], $this->kriteriaDinamisValidationRules()));

        // Satu record per supplier — jika sudah ada, arahkan ke edit
        $existing = SawNilaiHistoris::where('supplier_id', $validated['supplier_id'])->first();
        if ($existing) {
            return redirect()->route('saw.historis.edit', $existing->id)
                ->with('info', 'Supplier ini sudah memiliki data historis. Silakan perbarui di sini.');
        }

        $nilaiKriteria = $validated['nilai_kriteria'] ?? [];
        unset($validated['nilai_kriteria']);
        $validated['jumlah_transaksi_manual'] = $validated['jumlah_transaksi'] ?? 0;

        $historis = SawNilaiHistoris::create($validated);

        $this->syncNilaiKriteria($historis, $nilaiKriteria);

        return redirect()->route('saw.historis.index')
            ->with('success', 'Data historis berhasil ditambahkan.');
    }

    public function edit(SawNilaiHistoris $historis)
    {
        $this->denyViewOnly();

        $suppliers       = Supplier::orderBy('nama_supplier')->get();
        $kriteriaDinamis = $this->kriteriaDinamisAktif();
        $nilaiDinamis    = $historis->details->keyBy('kriteria_id');

        return view('pages.procurement.saw_historis.form', compact('historis', 'suppliers', 'kriteriaDinamis', 'nilaiDinamis'));
    }

    public function update(Request $request, SawNilaiHistoris $historis)
    {
        $this->denyViewOnly();

        $this->normalizeDecimalInputs($request);

        $validated = $request->validate(array_merge([
            'supplier_id'      => 'required|exists:suppliers,id_supplier',
            'periode_mulai'    => 'required|date',
            'periode_akhir'    => 'required|date|after_or_equal:periode_mulai',
            'jumlah_transaksi' => 'nullable|integer|min:0',
            'catatan'          => 'nullable|string|max:500',
        ], $this->kriteriaDinamisValidationRules()));

        $nilaiKriteria = $validated['nilai_kriteria'] ?? [];
        unset($validated['nilai_kriteria']);
        $validated['jumlah_transaksi_manual'] = $validated['jumlah_transaksi'] ?? 0;

        $historis->update($validated);

        $this->syncNilaiKriteria($historis, $nilaiKriteria);

        return redirect()->route('saw.historis.index')
            ->with('success', 'Data historis berhasil diperbarui.');
    }

    public function destroy(SawNilaiHistoris $historis)
    {
        $this->denyViewOnly();

        $historis->delete();

        return redirect()->route('saw.historis.index')
            ->with('success', 'Data historis berhasil dihapus.');
    }

    public function syncSupplier(int $supplierId, SupplierPerformanceService $service)
    {
        $this->denyViewOnly();

        $supplier = Supplier::findOrFail($supplierId);
        $service->recalculate($supplierId);

        return redirect()->route('saw.historis.index')
            ->with('success', "Data historis {$supplier->nama_supplier} berhasil disinkronkan dari transaksi.");
    }

    public function syncAll(SupplierPerformanceService $service)
    {
        $this->denyViewOnly();

        $suppliers = Supplier::all();
        foreach ($suppliers as $supplier) {
            $service->recalculate($supplier->id_supplier);
        }

        return redirect()->route('saw.historis.index')
            ->with('success', "Data historis {$suppliers->count()} supplier berhasil disinkronkan.");
    }

    /**
     * Bagian Pembelian (role 'procurement') hanya boleh melihat data kinerja
     * supplier, tidak boleh menambah/mengubah/menghapus/menyinkronkan.
     */
    private function denyViewOnly(): void
    {
        if (auth()->user()->role === 'procurement') {
            abort(403, 'Bagian Pembelian hanya dapat melihat data kinerja supplier.');
        }
    }

    /**
     * Kriteria aktif di luar C1-C6 — dipakai index() untuk kolom EKSTRA di
     * tabel (C7 dst), terpisah dari 5 kolom C2-C6 yang render khusus.
     */
    private function kriteriaCustomAktif()
    {
        return SawKriteria::aktif()
            ->whereNotIn('kode', ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'])
            ->get();
    }

    /**
     * Semua kriteria aktif di luar C1 (yang selalu dari Supplier Inquiry) —
     * inilah yang diinput manual lewat form ini, C2-C6 maupun custom (C7 dst),
     * semua tersimpan generik di saw_nilai_historis_detail per kode.
     */
    private function kriteriaDinamisAktif()
    {
        return SawKriteria::aktif()->where('kode', '!=', 'C1')->get();
    }

    private function kriteriaDinamisValidationRules(): array
    {
        $rules = [];
        foreach ($this->kriteriaDinamisAktif() as $k) {
            $rules["nilai_kriteria.{$k->id}"] = match ($k->kode) {
                'C2', 'C6' => 'nullable|integer|min:1|max:5',
                'C4', 'C5' => 'nullable|numeric|min:0|max:100',
                default    => 'nullable|numeric|min:0', // C3, C7+
            };
        }

        return $rules;
    }

    /**
     * Simpan/perbarui/hapus nilai kriteria di saw_nilai_historis_detail sesuai
     * input form ini. Field yang dikosongkan -> baris detailnya dihapus.
     */
    private function syncNilaiKriteria(SawNilaiHistoris $historis, array $nilaiKriteria): void
    {
        foreach ($nilaiKriteria as $kriteriaId => $nilai) {
            if ($nilai === null || $nilai === '') {
                SawNilaiHistorisDetail::where('historis_id', $historis->id)
                    ->where('kriteria_id', $kriteriaId)
                    ->delete();
                continue;
            }

            SawNilaiHistorisDetail::updateOrCreate(
                ['historis_id' => $historis->id, 'kriteria_id' => $kriteriaId],
                ['nilai' => $nilai]
            );
        }
    }

    /**
     * Field nilai kriteria berupa input teks (bukan type="number") supaya orang
     * bisa mengetik koma sebagai desimal — ganti ke titik di sini sebelum
     * divalidasi sebagai `numeric`.
     */
    private function normalizeDecimalInputs(Request $request): void
    {
        $clean = fn ($v) => $v === null || $v === '' ? $v : str_replace(',', '.', $v);

        $request->merge([
            'nilai_kriteria' => collect($request->input('nilai_kriteria', []))
                ->map($clean)->all(),
        ]);
    }
}
