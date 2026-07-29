<?php

namespace App\Http\Controllers\Procurement;

use App\Models\Receipt;
use App\Models\Needlist;
use App\Models\ReceiptItem;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ReceiptController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier'])
            ->withCount('receipts')
            ->whereIn('status', ['open', 'partial_received'])
            ->orderBy('tanggal_po', 'desc')
            ->get();

        return view('pages.procurement.receipt.index', compact('purchaseOrders'));
    }

    public function create(PurchaseOrder $po)
    {
        if ($po->status === 'completed') {
            return redirect()->route('receipts.index')->with('error', 'PO ini sudah selesai diterima.');
        }

        $po->load(['supplier', 'items.variasi.m_barang', 'items.receiptItems']);
        $riwayat = $po->receipts()->with('items.variasi')->orderBy('tanggal_terima', 'desc')->get();

        return view('pages.procurement.receipt.create', compact('po', 'riwayat'));
    }

    public function store(Request $request, PurchaseOrder $po)
    {
        if ($po->status === 'completed') {
            return back()->with('error', 'PO ini sudah selesai diterima.');
        }

        $validated = $request->validate([
            'tanggal_terima' => 'required|date',
            'items'          => 'required|array',
            'items.*.qty_received' => 'nullable|integer|min:0',
        ]);

        $po->load('items');

        // ── Validasi server-side: tidak boleh melebihi sisa, dan minimal 1 item diisi ──
        $errors  = [];
        $toSave  = [];
        $anyQty  = false;

        foreach ($validated['items'] as $poItemId => $row) {
            $qty = (int) ($row['qty_received'] ?? 0);
            if ($qty <= 0) continue;

            $poItem = $po->items->firstWhere('id', (int) $poItemId);
            if (!$poItem) continue;

            $sudahTerima = $poItem->receiptItems()->sum('qty_received');
            $sisa = $poItem->qty_order - $sudahTerima;

            if ($qty > $sisa) {
                $errors[] = "Qty terima untuk item #{$poItemId} ({$qty}) melebihi sisa yang bisa diterima ({$sisa}).";
                continue;
            }

            $anyQty = true;
            $toSave[] = ['poItem' => $poItem, 'qty' => $qty];
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        if (!$anyQty) {
            return back()->with('error', 'Tidak ada barang yang diterima. Isi minimal 1 qty terima.')->withInput();
        }

        DB::beginTransaction();
        try {
            $nextId = (Receipt::max('id') ?? 0) + 1;
            $kode = 'RC-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $receipt = Receipt::create([
                'kode_receipt'      => $kode,
                'purchase_order_id' => $po->id,
                'tanggal_terima'    => $validated['tanggal_terima'],
                'user_id'           => auth()->id(),
            ]);

            foreach ($toSave as $row) {
                /** @var PurchaseOrderItem $poItem */
                $poItem = $row['poItem'];
                $qty    = $row['qty'];

                ReceiptItem::create([
                    'receipt_id'             => $receipt->id,
                    'purchase_order_item_id' => $poItem->id,
                    'id_variasi'             => $poItem->id_variasi,
                    'qty_order'              => $poItem->qty_order,
                    'qty_received'           => $qty,
                ]);

                $poItem->increment('qty_received', $qty);

                DB::table('variasis')
                    ->where('id_variasi', $poItem->id_variasi)
                    ->increment('stock', $qty);
            }

            // Update status PO: completed jika semua item sudah terpenuhi penuh.
            $allCompleted = $po->items->every(
                fn ($item) => ReceiptItem::where('purchase_order_item_id', $item->id)->sum('qty_received') >= $item->qty_order
            );

            if ($allCompleted) {
                $po->update(['status' => 'completed', 'closed_at' => now()]);
            } else {
                $po->update(['status' => 'partial_received']);
            }

            $allPoCompleted = PurchaseOrder::where('needlist_id', $po->needlist_id)
                ->where('status', '!=', 'completed')
                ->doesntExist();

            if ($allPoCompleted) {
                Needlist::where('id', $po->needlist_id)->update(['status' => 'completed']);
            }

            DB::commit();

            return redirect()
                ->route('receipts.show', $receipt->id)
                ->with('success', 'Penerimaan berhasil disimpan.');

        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan penerimaan: ' . $th->getMessage())->withInput();
        }
    }

    public function tutup(Request $request, PurchaseOrder $po)
    {
        if ($po->status !== 'partial_received') {
            return back()->with('error', 'PO hanya bisa ditutup manual jika statusnya sedang partial received.');
        }

        $validated = $request->validate([
            'catatan_tutup' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $po->update([
                'status'          => 'completed',
                'closed_at'       => now(),
                'is_force_closed' => true,
                'catatan_tutup'   => $validated['catatan_tutup'],
            ]);

            $allPoCompleted = PurchaseOrder::where('needlist_id', $po->needlist_id)
                ->where('status', '!=', 'completed')
                ->doesntExist();

            if ($allPoCompleted) {
                Needlist::where('id', $po->needlist_id)->update(['status' => 'completed']);
            }

            DB::commit();

            return redirect()
                ->route('receipts.index')
                ->with('success', 'PO berhasil ditutup.');

        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', 'Gagal menutup PO: ' . $th->getMessage())->withInput();
        }
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['items.variasi.m_barang', 'purchaseOrder.supplier', 'user']);

        return view('pages.procurement.receipt.show', compact('receipt'));
    }
}
