<?php

namespace App\Services;

use App\Models\SawNilaiHistoris;
use Carbon\Carbon;

/**
 * Tentukan estimasi lead time (hari) & tanggal estimasi tiba untuk satu supplier.
 *
 * Dipakai di tabel Pemilihan Supplier supaya SEMUA baris (bukan hanya yang
 * direkomendasikan SAW) memakai rumus yang sama: data historis kalau ada,
 * fallback ke selisih hari antara estimasi_pengiriman dan tanggal inquiry.
 * Tanpa ini, baris yang belum ikut dihitung SAW akan menampilkan tanggal
 * mentah dari penawaran (bisa di masa lalu) sementara baris yang sudah
 * dihitung menampilkan tanggal "hari ini + lead time" — tidak konsisten.
 */
class SupplierLeadTimeResolver
{
    /**
     * @return array{days: float, sumber: string} sumber: 'historis'|'penawaran'
     */
    public function resolveDays(int $supplierId, ?string $estimasiPengiriman, ?Carbon $inquiryCreatedAt): array
    {
        $historis = SawNilaiHistoris::where('supplier_id', $supplierId)
            ->orderByDesc('periode_akhir')
            ->first();

        if ($historis && !is_null($historis->lead_time) && $historis->lead_time > 0) {
            return ['days' => (float) $historis->lead_time, 'sumber' => 'historis'];
        }

        if ($estimasiPengiriman && $inquiryCreatedAt) {
            $days = max(1, abs(Carbon::parse($estimasiPengiriman)->diffInDays($inquiryCreatedAt)));
            return ['days' => (float) $days, 'sumber' => 'penawaran'];
        }

        return ['days' => 0.0, 'sumber' => 'penawaran'];
    }

    /**
     * @return array{hari: float, tanggal: string, sumber: string}
     */
    public function estimasiTiba(int $supplierId, ?string $estimasiPengiriman, ?Carbon $inquiryCreatedAt): array
    {
        $resolved = $this->resolveDays($supplierId, $estimasiPengiriman, $inquiryCreatedAt);

        return [
            'hari'    => $resolved['days'],
            'tanggal' => Carbon::now()->addDays((int) round($resolved['days']))->format('d M Y'),
            'sumber'  => $resolved['sumber'],
        ];
    }
}
