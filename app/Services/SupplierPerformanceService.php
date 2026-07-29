<?php

namespace App\Services;

use App\Models\SawNilaiHistoris;
use Illuminate\Support\Facades\DB;

class SupplierPerformanceService
{
    /**
     * Hitung ulang C3, C4, C5 dengan menggabungkan:
     *   - Seed manual (nilai yang diinput admin × jumlah_transaksi_manual)
     *   - Data aktual dari PO + Receipt (dihitung dari DB)
     *
     * Formula: (seed × N_manual + aktual × N_po) / (N_manual + N_po)
     *
     * Kolom *_manual TIDAK pernah diubah di sini — hanya dibaca sebagai seed.
     * C2 (Termin) dan C6 (Komunikasi) tidak disentuh.
     *
     * Hanya PO dengan status 'completed' yang dihitung (baik selesai penuh lewat
     * receipt, maupun ditutup manual via ReceiptController::tutup) — PO yang masih
     * open/partial_received belum final sehingga belum ikut dirata-rata.
     */
    public function recalculate(int $supplierId): void
    {
        $record = SawNilaiHistoris::where('supplier_id', $supplierId)->first();

        // Jika belum ada record manual, tidak ada yang bisa dihitung
        if (!$record) return;

        // ── Baca seed manual ────────────────────────────────────────────────────────
        $seedN  = (int) ($record->jumlah_transaksi_manual ?? 0);
        $seedC3 = $record->lead_time_manual;
        $seedC4 = $record->akurasi_kuantitas_manual;
        $seedC5 = $record->tingkat_pemenuhan_manual;

        // ── Jumlah transaksi aktual = jumlah PO supplier ini yang sudah completed ──
        $actualN = DB::table('purchase_orders')
            ->where('supplier_id', $supplierId)
            ->where('status', 'completed')
            ->count();

        // ── C3 aktual: rata-rata lead time per PO (tanggal PO → tanggal PO selesai/ditutup) ──
        $actualC3 = DB::table('purchase_orders')
            ->where('supplier_id', $supplierId)
            ->where('status', 'completed')
            ->whereNotNull('closed_at')
            ->selectRaw('AVG(DATEDIFF(closed_at, tanggal_po)) as avg_lead')
            ->value('avg_lead');

        $actualC3 = $actualC3 !== null ? (float) $actualC3 : null;

        // ── C4 aktual: akurasi kuantitas rata-rata (%), hanya item yang dikirim ──
        // (item yang tidak pernah dikirim otomatis tidak ikut, bukan dihitung 0)
        $c4Raw = DB::table('receipt_items as ri')
            ->join('purchase_order_items as poi', 'poi.id', '=', 'ri.purchase_order_item_id')
            ->join('purchase_orders as po', 'po.id', '=', 'poi.purchase_order_id')
            ->where('po.supplier_id', $supplierId)
            ->where('po.status', 'completed')
            ->where('poi.qty_order', '>', 0)
            ->selectRaw('AVG(LEAST(ri.qty_received, poi.qty_order) / poi.qty_order * 100) as acc')
            ->value('acc');

        $actualC4 = $c4Raw !== null ? (float) $c4Raw : null;

        // ── C5 aktual: tingkat pemenuhan rata-rata (%) per PO ────────────────────
        // Dihitung dari jenis item yang DIKIRIM (qty diterima > 0), tanpa
        // mempedulikan apakah qty-nya penuh atau tidak.
        $poIds = DB::table('purchase_orders')
            ->where('supplier_id', $supplierId)
            ->where('status', 'completed')
            ->pluck('id');

        $actualC5 = null;
        if ($poIds->isNotEmpty()) {
            $rates = $poIds->map(function ($poId) {
                $total = DB::table('purchase_order_items')
                    ->where('purchase_order_id', $poId)
                    ->count();

                if ($total === 0) return null;

                $dikirim = DB::table('purchase_order_items as poi')
                    ->where('poi.purchase_order_id', $poId)
                    ->whereRaw(
                        '(SELECT COALESCE(SUM(ri.qty_received),0)
                          FROM receipt_items ri
                          WHERE ri.purchase_order_item_id = poi.id) > 0'
                    )
                    ->count();

                return ($dikirim / $total) * 100;
            })->filter()->values();

            $actualC5 = $rates->isNotEmpty() ? $rates->avg() : null;
        }

        // ── Weighted average: gabung seed manual + data aktual ───────────────────
        $wavg = function (?float $seed, ?float $actual, int $nSeed, int $nActual): ?float {
            if ($nSeed > 0 && $nActual > 0 && $seed !== null && $actual !== null) {
                return ($seed * $nSeed + $actual * $nActual) / ($nSeed + $nActual);
            }
            if ($nActual > 0 && $actual !== null) return $actual;
            if ($nSeed > 0 && $seed !== null)     return $seed;
            return null;
        };

        $record->update([
            'lead_time'         => $wavg($seedC3, $actualC3, $seedN, $actualN) !== null
                                    ? round($wavg($seedC3, $actualC3, $seedN, $actualN), 2) : null,
            'akurasi_kuantitas' => $wavg($seedC4, $actualC4, $seedN, $actualN) !== null
                                    ? round($wavg($seedC4, $actualC4, $seedN, $actualN), 2) : null,
            'tingkat_pemenuhan' => $wavg($seedC5, $actualC5, $seedN, $actualN) !== null
                                    ? round($wavg($seedC5, $actualC5, $seedN, $actualN), 2) : null,
            'jumlah_transaksi'  => $seedN + $actualN,
            'periode_akhir'     => now()->toDateString(),
        ]);
    }
}
