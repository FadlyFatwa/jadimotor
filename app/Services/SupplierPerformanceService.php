<?php

namespace App\Services;

use App\Models\SawKriteria;
use App\Models\SawNilaiHistoris;
use App\Models\SawNilaiHistorisDetail;
use Illuminate\Support\Facades\DB;

class SupplierPerformanceService
{
    /**
     * Hitung ulang C3, C4, C5 dengan menggabungkan:
     *   - Seed (nilai kriteria yang tersimpan saat ini × jumlah_transaksi_manual)
     *   - Data aktual dari PO + Receipt (dihitung dari DB)
     *
     * Formula: (seed × N_manual + aktual × N_po) / (N_manual + N_po)
     *
     * C2 (Termin) dan C6 (Komunikasi) tidak disentuh.
     *
     * Hanya PO dengan status 'completed' yang dihitung (baik selesai penuh lewat
     * receipt, maupun ditutup manual via ReceiptController::tutup) — PO yang masih
     * open/partial_received belum final sehingga belum ikut dirata-rata.
     *
     * NB: method ini tidak dipanggil dari route/UI manapun saat ini (dead code,
     * sinkronisasi otomatis di luar scope skripsi) — tetap dijaga konsisten
     * dengan skema saw_nilai_historis_detail supaya tidak error kalau dipanggil.
     */
    public function recalculate(int $supplierId): void
    {
        $record = SawNilaiHistoris::where('supplier_id', $supplierId)->with('details')->first();

        // Jika belum ada record manual, tidak ada yang bisa dihitung
        if (!$record) return;

        $kriteriaIds = SawKriteria::whereIn('kode', ['C3', 'C4', 'C5'])->pluck('id', 'kode');

        // ── Baca nilai yang tersimpan saat ini sebagai seed ────────────────────────
        $seedN  = (int) ($record->jumlah_transaksi_manual ?? 0);
        $seedVal = function (string $kode) use ($record, $kriteriaIds): ?float {
            $nilai = isset($kriteriaIds[$kode])
                ? $record->details->firstWhere('kriteria_id', $kriteriaIds[$kode])?->nilai
                : null;
            return $nilai !== null ? (float) $nilai : null;
        };
        $seedC3 = $seedVal('C3');
        $seedC4 = $seedVal('C4');
        $seedC5 = $seedVal('C5');

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

        $blended = [
            'C3' => $wavg($seedC3, $actualC3, $seedN, $actualN),
            'C4' => $wavg($seedC4, $actualC4, $seedN, $actualN),
            'C5' => $wavg($seedC5, $actualC5, $seedN, $actualN),
        ];

        foreach ($blended as $kode => $nilai) {
            if ($nilai === null || !isset($kriteriaIds[$kode])) {
                continue;
            }

            SawNilaiHistorisDetail::updateOrCreate(
                ['historis_id' => $record->id, 'kriteria_id' => $kriteriaIds[$kode]],
                ['nilai' => round($nilai, 2)]
            );
        }

        $record->update([
            'jumlah_transaksi' => $seedN + $actualN,
            'periode_akhir'    => now()->toDateString(),
        ]);
    }
}
