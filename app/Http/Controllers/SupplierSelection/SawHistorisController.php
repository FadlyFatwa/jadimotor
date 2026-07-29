<?php

namespace App\Http\Controllers\SupplierSelection;

use App\Http\Controllers\Controller;
use App\Models\SawNilaiHistoris;
use App\Models\Supplier;
use App\Services\SupplierPerformanceService;
use Illuminate\Http\Request;

class SawHistorisController extends Controller
{
    public function index(Request $request)
    {
        $query = SawNilaiHistoris::with('supplier')
            ->latest('periode_akhir');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $historis  = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::orderBy('nama_supplier')->get();

        return view('pages.procurement.saw_historis.index', compact('historis', 'suppliers'));
    }

    public function create()
    {
        $this->denyViewOnly();

        $suppliers = Supplier::orderBy('nama_supplier')->get();

        return view('pages.procurement.saw_historis.form', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $this->denyViewOnly();

        $this->normalizeDecimalInputs($request);

        $validated = $request->validate([
            'supplier_id'        => 'required|exists:suppliers,id_supplier',
            'periode_mulai'      => 'required|date',
            'periode_akhir'      => 'required|date|after_or_equal:periode_mulai',
            'termin_pembayaran'  => 'nullable|integer|min:1|max:5',
            'lead_time'          => 'nullable|numeric|min:0',
            'akurasi_kuantitas'  => 'nullable|numeric|min:0|max:100',
            'tingkat_pemenuhan'  => 'nullable|numeric|min:0|max:100',
            'komunikasi'         => 'nullable|integer|min:1|max:5',
            'jumlah_transaksi'   => 'nullable|integer|min:0',
            'catatan'            => 'nullable|string|max:500',
        ]);

        // Satu record per supplier — jika sudah ada, arahkan ke edit
        $existing = SawNilaiHistoris::where('supplier_id', $validated['supplier_id'])->first();
        if ($existing) {
            return redirect()->route('saw.historis.edit', $existing->id)
                ->with('info', 'Supplier ini sudah memiliki data historis. Silakan perbarui di sini.');
        }

        // Simpan nilai ke kolom aktif DAN kolom seed manual
        $validated['lead_time_manual']         = $validated['lead_time'] ?? null;
        $validated['akurasi_kuantitas_manual'] = $validated['akurasi_kuantitas'] ?? null;
        $validated['tingkat_pemenuhan_manual'] = $validated['tingkat_pemenuhan'] ?? null;
        $validated['jumlah_transaksi_manual']  = $validated['jumlah_transaksi'] ?? 0;

        SawNilaiHistoris::create($validated);

        return redirect()->route('saw.historis.index')
            ->with('success', 'Data historis berhasil ditambahkan.');
    }

    public function edit(SawNilaiHistoris $historis)
    {
        $this->denyViewOnly();

        $suppliers = Supplier::orderBy('nama_supplier')->get();

        return view('pages.procurement.saw_historis.form', compact('historis', 'suppliers'));
    }

    public function update(Request $request, SawNilaiHistoris $historis)
    {
        $this->denyViewOnly();

        $this->normalizeDecimalInputs($request);

        $validated = $request->validate([
            'supplier_id'        => 'required|exists:suppliers,id_supplier',
            'periode_mulai'      => 'required|date',
            'periode_akhir'      => 'required|date|after_or_equal:periode_mulai',
            'termin_pembayaran'  => 'nullable|integer|min:1|max:5',
            'lead_time'          => 'nullable|numeric|min:0',
            'akurasi_kuantitas'  => 'nullable|numeric|min:0|max:100',
            'tingkat_pemenuhan'  => 'nullable|numeric|min:0|max:100',
            'komunikasi'         => 'nullable|integer|min:1|max:5',
            'jumlah_transaksi'   => 'nullable|integer|min:0',
            'catatan'            => 'nullable|string|max:500',
        ]);

        // Perbarui seed manual sekaligus (dibaca SupplierPerformanceService kalau
        // sewaktu-waktu sinkronisasi otomatis diaktifkan kembali).
        $validated['lead_time_manual']         = $validated['lead_time'] ?? null;
        $validated['akurasi_kuantitas_manual'] = $validated['akurasi_kuantitas'] ?? null;
        $validated['tingkat_pemenuhan_manual'] = $validated['tingkat_pemenuhan'] ?? null;
        $validated['jumlah_transaksi_manual']  = $validated['jumlah_transaksi'] ?? 0;

        $historis->update($validated);

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
     * Field lead_time/akurasi_kuantitas/tingkat_pemenuhan berupa input teks (bukan
     * type="number") supaya orang bisa mengetik koma sebagai desimal — ganti ke
     * titik di sini sebelum divalidasi sebagai `numeric`.
     */
    private function normalizeDecimalInputs(Request $request): void
    {
        $clean = fn ($v) => $v === null || $v === '' ? $v : str_replace(',', '.', $v);

        $request->merge([
            'lead_time'         => $clean($request->input('lead_time')),
            'akurasi_kuantitas' => $clean($request->input('akurasi_kuantitas')),
            'tingkat_pemenuhan' => $clean($request->input('tingkat_pemenuhan')),
        ]);
    }
}
