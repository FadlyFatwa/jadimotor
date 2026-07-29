<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Needlist;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Models\SupplierVariasi;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SupplierInquiryController extends Controller
{
    /**
     * Buat inquiry untuk SEMUA supplier
     */
    public function storeAllFromNeedlist(Request $request, $needlist_id)
    {
        $needlist = Needlist::with(['details'])
            ->where('status', 'approved')
            ->findOrFail($needlist_id);

        // Ambil semua item aktif (bukan referensi, bukan rejected)
        $activeItems = $needlist->details
            ->where('is_reference', false)
            ->whereIn('status', ['pending', 'approved'])
            ->values();

        if ($activeItems->isEmpty()) {
            return redirect()
                ->route('needlist.show', $needlist_id)
                ->with('error', 'Tidak ada item aktif untuk dibuatkan inquiry.');
        }

        $variasiIds = $activeItems->pluck('id_variasi')->unique()->values();

        // Ambil SEMUA supplier yang bisa mensuplai variasi-variasi ini
        // (bukan hanya supplier pertama — pakai hasMany, bukan hasOne)
        $allSVs = SupplierVariasi::whereIn('id_variasi', $variasiIds)->get();

        // Build map: supplier_id → [id_variasi, ...]
        $supplierToVariasiIds = [];
        foreach ($allSVs as $sv) {
            $supplierToVariasiIds[$sv->id_supplier][] = $sv->id_variasi;
        }

        // Build map: id_variasi → qty (dari needlist item)
        $variasiQtyMap = $activeItems->keyBy('id_variasi')->map(fn($d) => $d->qty);

        foreach ($supplierToVariasiIds as $supplierId => $svVariasiIds) {
            // Skip jika inquiry untuk supplier ini sudah ada
            if (SupplierInquiry::where('needlist_id', $needlist_id)
                ->where('supplier_id', $supplierId)->exists()) {
                continue;
            }

            $inquiry = SupplierInquiry::create([
                'needlist_id' => $needlist_id,
                'supplier_id' => $supplierId,
                'status'      => 'waiting_response',
            ]);

            foreach ($svVariasiIds as $idVariasi) {
                // Hanya buat item jika variasi ada di needlist aktif
                if (!isset($variasiQtyMap[$idVariasi])) continue;

                SupplierInquiryItem::create([
                    'inquiry_id' => $inquiry->id,
                    'id_variasi' => $idVariasi,
                    'qty'        => $variasiQtyMap[$idVariasi],
                ]);
            }
        }

        $needlist->update(['status' => 'inquiry_created']);

        return redirect()
            ->route('needlist.show', $needlist_id)
            ->with('success', 'Inquiry berhasil dibuat untuk semua supplier ('. count($supplierToVariasiIds) .' supplier).');
    }


    public function generatePdf($id)
    {
        $inquiry = SupplierInquiry::with(['supplier', 'items'])->findOrFail($id);


        $pdf = Pdf::loadView('pages.procurement.supplier_inquiry.pdf', compact('inquiry'))
                ->setPaper('a4', 'portrait');

        return $pdf->stream('Inquiry_' . $inquiry->supplier->nama_supplier . '.pdf');
    }

    public function storeResponse(Request $request, $id)
    {
        $inquiry = SupplierInquiry::findOrFail($id);

        foreach ($request->items as $itemId => $data) {
            SupplierInquiryItem::where('id', $itemId)->update([
                'harga_penawaran' => $data['harga_penawaran'],
                'estimasi_pengiriman' => $data['estimasi_pengiriman'] ?? null,
                
            ]);
        }

        $inquiry->update(['status' => 'responded']);

        // Auto-transition: jika semua inquiry needlist sudah responded → selection_in_progress
        $needlist = $inquiry->needlist;
        if ($needlist && $needlist->status === 'inquiry_created') {
            $allResponded = $needlist->supplierInquiries()
                ->where('status', '!=', 'responded')
                ->doesntExist();

            if ($allResponded) {
                $needlist->update(['status' => 'selection_in_progress']);
            }
        }

        return redirect()->route('needlist.show', $inquiry->needlist_id)
            ->with('success', 'Konfirmasi harga berhasil disimpan.');
    }

    public function fillModal($id)
    {
        $inquiry = SupplierInquiry::with('items.variasi.m_barang')->findOrFail($id);

        // Ambil harga beli historis dari supplier_barang untuk supplier ini
        $variasiIds = $inquiry->items->pluck('id_variasi');
        $hargaHistoris = SupplierVariasi::where('id_supplier', $inquiry->supplier_id)
            ->whereIn('id_variasi', $variasiIds)
            ->pluck('harga_beli', 'id_variasi');

        $defaultEstimasi = now()->addDay()->format('Y-m-d\TH:i');

        return view('pages.procurement.supplier_inquiry.modal-fill',
            compact('inquiry', 'hargaHistoris', 'defaultEstimasi'));
    }


    public function previewModal($id)
    {
        $inquiry = SupplierInquiry::with('items.variasi.m_barang')->findOrFail($id);

        return view('pages.procurement.supplier_inquiry.modal-preview', compact('inquiry'));
    }

}
