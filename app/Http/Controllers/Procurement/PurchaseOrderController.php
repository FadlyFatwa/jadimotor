<?php

namespace App\Http\Controllers\Procurement;

use Illuminate\Http\Request;
use App\Models\Needlist;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;

class PurchaseOrderController extends Controller
{
    public function show($id)
    {
        $po = PurchaseOrder::with([
            'supplier',
            'needlist',
            'items.variasi.m_barang'
        ])->findOrFail($id);

        return view('pages.procurement.purchase_order.show', compact('po'));
    }

    public function print($id)
    {
        $po = PurchaseOrder::with([
            'supplier',
            'needlist',
            'items.variasi.m_barang'
        ])->findOrFail($id);

        $pdf = Pdf::loadView(
            'pages.procurement.purchase_order.print',
            compact('po')
        )->setPaper('A4', 'portrait');

        return $pdf->stream('PO-' . $po->kode_po . '.pdf');
    }

    public function createFromNeedlist(Request $request, $needlist_id)
    {
        $needlist = Needlist::findOrFail($needlist_id);

        if ($needlist->status === 'po_issued') {
            return redirect()
                ->to(route('needlist.show', $needlist_id) . '#pane-selection')
                ->with('error', 'PO sudah dibuat untuk needlist ini.');
        }

        if (PurchaseOrder::where('needlist_id', $needlist_id)->exists()) {
            return redirect()
                ->to(route('needlist.show', $needlist_id) . '#pane-selection')
                ->with('error', 'PO untuk needlist ini sudah ada.');
        }

        $inquiryIds = SupplierInquiry::where('needlist_id', $needlist_id)->pluck('id');

        if ($inquiryIds->isEmpty()) {
            return redirect()
                ->to(route('needlist.show', $needlist_id) . '#pane-selection')
                ->with('error', 'Tidak ada inquiry terkait needlist ini.');
        }

        // Jika ada selected_items dikirim dari form → proses ulang (backward compat)
        // Jika tidak ada → gunakan status 'selected' yang sudah disimpan via saveSelection()
        if ($request->has('selected_items')) {
            $allItemIds  = SupplierInquiryItem::whereIn('inquiry_id', $inquiryIds)->pluck('id')->toArray();
            $selectedIds = array_intersect($request->input('selected_items', []), $allItemIds);

            SupplierInquiryItem::whereIn('inquiry_id', $inquiryIds)->update(['status' => 'pending']);
            if (!empty($selectedIds)) {
                SupplierInquiryItem::whereIn('id', $selectedIds)->update(['status' => 'selected']);
            }
        }

        $items = SupplierInquiryItem::whereIn('inquiry_id', $inquiryIds)
            ->where('status', 'selected')
            ->with(['inquiry', 'variasi'])
            ->get();

        if ($items->isEmpty()) {
            return redirect()
                ->to(route('needlist.show', $needlist_id) . '#pane-selection')
                ->with('error', 'Tidak ada item yang dipilih. Pilih supplier terlebih dahulu.');
        }

        // Validasi: tiap variasi yang punya penawaran harus ada ≥1 yang dipilih
        $variasiDenganPenawaran = SupplierInquiryItem::whereIn('inquiry_id', $inquiryIds)
            ->whereNotNull('harga_penawaran')
            ->pluck('id_variasi')->unique();

        $variasiTerpilih = $items->pluck('id_variasi')->unique();
        $variasiKosong   = $variasiDenganPenawaran->diff($variasiTerpilih);

        if ($variasiKosong->isNotEmpty()) {
            return redirect()
                ->to(route('needlist.show', $needlist_id) . '#pane-selection')
                ->with('error', 'Pilihan belum lengkap. Setiap variasi harus memiliki minimal 1 supplier terpilih sebelum PO dapat diterbitkan.');
        }

        if (
            SupplierInquiryItem::whereIn('inquiry_id', $inquiryIds)
                ->where('status', 'selected')
                ->whereNull('harga_penawaran')
                ->exists()
        ) {
            return redirect()
                ->to(route('needlist.show', $needlist_id) . '#pane-selection')
                ->with('error', 'Masih ada harga yang belum dikonfirmasi untuk item yang dipilih.');
        }

        DB::beginTransaction();
        try {
            $groupedBySupplier = $items->groupBy(fn ($item) => $item->inquiry->supplier_id);

            foreach ($groupedBySupplier as $supplier_id => $itemsPerSupplier) {

                $nextId = (PurchaseOrder::max('id') ?? 0) + 1;
                $kodePo = 'PO-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

                $po = PurchaseOrder::create([
                    'kode_po' => $kodePo,
                    'supplier_id' => $supplier_id,
                    'needlist_id' => $needlist_id,
                    'tanggal_po' => now(),
                    'status' => 'open',
                ]);

                foreach ($itemsPerSupplier as $it) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'inquiry_id' => $it->inquiry_id,
                        'id_variasi' => $it->id_variasi,
                        'qty_order' => $it->qty,
                        'harga_beli' => $it->harga_penawaran,
                    ]);
                }
            }

            $needlist->update(['status' => 'po_issued']);

            DB::commit();

            return redirect()
                ->route('needlist.show', $needlist_id)
                ->with('success', 'Purchase Order berhasil dibuat.');

        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()
                ->to(route('needlist.show', $needlist_id) . '#pane-selection')
                ->with('error', 'Gagal membuat PO: ' . $th->getMessage());
        }
    }

}
