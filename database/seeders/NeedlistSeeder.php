<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Variasi;
use App\Models\SupplierVariasi;
use App\Models\Needlist;
use App\Models\NeedlistItem;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;

/**
 * Seeder pengadaan — menggunakan 35 variasi NYATA (barcode dari katalog asli
 * yang sudah diimpor, bukan data demo). Dipakai untuk menguji seluruh alur
 * procurement: Daftar Kebutuhan → Persetujuan → Penawaran → Pemilihan Supplier.
 *
 * Tanggal needlist disebar 1–12 Juni 2026. Status sengaja dikonsentrasikan
 * di 'selection_in_progress' (4 dari 8 needlist) sesuai permintaan, supaya
 * ada banyak data nyata untuk uji tab "Pemilihan Supplier".
 */
class NeedlistSeeder extends Seeder
{
    /** @var array<string,int> barcode => id_variasi */
    private array $variasiMap = [];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('saw_rekomendasi')->truncate();
        DB::table('saw_perhitungan_detail')->truncate();
        DB::table('saw_perhitungan')->truncate();
        DB::table('purchase_order_items')->truncate();
        DB::table('purchase_orders')->truncate();
        DB::table('receipt_items')->truncate();
        DB::table('receipts')->truncate();
        DB::table('supplier_inquiry_items')->truncate();
        DB::table('supplier_inquiries')->truncate();
        DB::table('needlist_items')->truncate();
        DB::table('needlists')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $procurement = User::where('role', 'procurement')->first();
        if (!$procurement) { $this->command->error('UserSeeder harus dijalankan dulu.'); return; }

        // ── 35 barcode nyata, dikelompokkan per master barang ──────────────
        $batches = [
            // Fanbelt, Coil, Motor Fan Radiator
            ['kode'=>'NL-2026-101','tanggal'=>'2026-06-01','status'=>'draft',
                'items'=>['01128'=>8,'10541'=>6,'02327'=>4,'03969'=>3,'06107'=>3]],
            // Bosh Arm Big (bagian 1)
            ['kode'=>'NL-2026-102','tanggal'=>'2026-06-02','status'=>'submitted',
                'items'=>['04004'=>10,'04010'=>10,'13734'=>10,'13735'=>10]],
            // Bosh Arm Big (bagian 2)
            ['kode'=>'NL-2026-103','tanggal'=>'2026-06-03','status'=>'approved',
                'items'=>['13828'=>8,'14616'=>8,'14811'=>8,'16370'=>6]],
            // Support Shock FR (bagian 1)
            ['kode'=>'NL-2026-104','tanggal'=>'2026-06-05','status'=>'inquiry_created',
                'items'=>['03731'=>6,'02933'=>6,'09382'=>5,'10471'=>5,'13736'=>5]],
            // Support Shock FR (bagian 2) — sudah ada progres pemilihan sebagian
            ['kode'=>'NL-2026-105','tanggal'=>'2026-06-06','status'=>'selection_in_progress',
                'items'=>['13738'=>6,'13904'=>6,'14965'=>5,'16400'=>5,'16544'=>5], 'selectionRatio'=>0.6],
            // Ball Joint, Link Stabil, Tensioner — sudah ada progres pemilihan sebagian
            ['kode'=>'NL-2026-106','tanggal'=>'2026-06-08','status'=>'selection_in_progress',
                'items'=>['05021'=>10,'05786'=>10,'03631'=>8,'08972'=>4], 'selectionRatio'=>0.6],
            // Filter & part besar (Rack Steer, Drek Laher) — penawaran masuk, belum mulai dipilih
            ['kode'=>'NL-2026-107','tanggal'=>'2026-06-10','status'=>'selection_in_progress',
                'items'=>['08979'=>20,'07151'=>15,'14274'=>2,'14966'=>4], 'selectionRatio'=>0.0],
            // Bosh Arm Small, Karet Stabil — penawaran masuk, belum mulai dipilih
            ['kode'=>'NL-2026-108','tanggal'=>'2026-06-12','status'=>'selection_in_progress',
                'items'=>['15032'=>6,'15786'=>6,'14455'=>10,'16403'=>10], 'selectionRatio'=>0.0],
            // Kebutuhan manual dari procurement (barcode+qty hasil input langsung).
            // 1 baris per barcode (qty digabung) — barcode yang sama selalu
            // mengarah ke variasi & supplier yang sama juga, jadi baris ganda
            // cuma bikin bingung di tampilan. 02387 & 02405 grade sama, beda merk.
            ['kode'=>'NL-2026-109','tanggal'=>'2026-06-13','status'=>'approved',
                'items'=>[
                    '02514'=>12,
                    '16752'=>2,
                    '02387'=>7,
                    '02405'=>7,
                    '11726'=>4,
                    '03071'=>2,
                    '09338'=>3,
                    '16685'=>3,
                    '13802'=>1,
                    '07262'=>1,
                    '11804'=>3,
                    '01646'=>5,
                    '09252'=>2,
                    '11415'=>5,
                ]],
        ];

        $allBarcodes = collect($batches)->flatMap(fn($b) => array_keys($b['items']))->unique()->values()->all();
        $this->variasiMap = Variasi::whereIn('barcode', $allBarcodes)->pluck('id_variasi', 'barcode')->all();

        $missing = array_diff($allBarcodes, array_keys($this->variasiMap));
        foreach ($missing as $bc) { $this->command->warn("Barcode $bc tidak ditemukan, dilewati."); }

        $totalItems = 0;
        $statusCount = [];
        foreach ($batches as $b) {
            $totalItems += $this->buildNeedlist(
                $procurement, $b['kode'], $b['tanggal'], $b['status'], $b['items'],
                $b['selectionRatio'] ?? 0.6
            );
            $statusCount[$b['status']] = ($statusCount[$b['status']] ?? 0) + 1;
        }

        $this->command->info("NeedlistSeeder selesai: ".count($batches)." needlist, $totalItems item dari ".count($this->variasiMap)." variasi nyata.");
        foreach ($statusCount as $status => $count) {
            $this->command->line("  - $status: $count needlist");
        }
    }

    private function buildNeedlist(User $procurement, string $kode, string $tanggal, string $status, array $items, float $selectionRatio = 0.6): int
    {
        $tglNeedlist = Carbon::parse($tanggal.' 09:00:00');

        // Item baru dianggap 'approved' kalau needlist sudah lewat tahap persetujuan.
        $itemStatus = in_array($status, ['draft', 'submitted']) ? 'pending' : 'approved';
        $approvalStatus = match (true) {
            $itemStatus === 'approved' => 'approved',
            $status === 'submitted'   => 'waiting',
            default                   => 'draft',
        };

        $needlist = Needlist::create([
            'kode_needlist'   => $kode,
            'user_id'         => $procurement->id,
            'status'          => $status,
            'approval_status' => $approvalStatus,
            'approved_by'     => $approvalStatus === 'approved' ? $procurement->id : null,
            'approved_at'     => $approvalStatus === 'approved' ? $tglNeedlist->copy()->addHours(4) : null,
        ]);
        $needlist->forceFill(['created_at' => $tglNeedlist, 'updated_at' => $tglNeedlist])->save();

        $variasiQty = [];
        foreach ($items as $barcode => $qty) {
            $idVariasi = $this->variasiMap[$barcode] ?? null;
            if (!$idVariasi) continue;

            $item = NeedlistItem::create([
                'needlist_id'  => $needlist->id,
                'id_variasi'   => $idVariasi,
                'qty'          => $qty,
                'status'       => $itemStatus,
                'is_reference' => false,
            ]);
            $item->forceFill(['created_at' => $tglNeedlist, 'updated_at' => $tglNeedlist])->save();

            $variasiQty[$idVariasi] = $qty;
        }

        if (in_array($status, ['inquiry_created', 'selection_in_progress', 'po_issued', 'completed'])) {
            $this->buildInquiries($needlist, $variasiQty, $tglNeedlist, $status, $selectionRatio);
        }

        return count($variasiQty);
    }

    private function buildInquiries(Needlist $needlist, array $variasiQty, Carbon $tglNeedlist, string $status, float $selectionRatio): void
    {
        $allSV = SupplierVariasi::whereIn('id_variasi', array_keys($variasiQty))->get()->groupBy('id_supplier');

        $tglInquiry  = $tglNeedlist->copy()->addDay();
        $tglResponse = $tglNeedlist->copy()->addDays(2);
        // 'inquiry_created' = inquiry baru dikirim, belum ada yang merespon.
        $isResponded = $status !== 'inquiry_created';

        foreach ($allSV as $idSupplier => $svRows) {
            $inquiry = SupplierInquiry::create([
                'needlist_id' => $needlist->id,
                'supplier_id' => $idSupplier,
                'status'      => $isResponded ? 'responded' : 'waiting_response',
            ]);
            $inquiryTs = $isResponded ? $tglResponse : $tglInquiry;
            $inquiry->forceFill(['created_at' => $tglInquiry, 'updated_at' => $inquiryTs])->save();

            foreach ($svRows as $sv) {
                $qty = $variasiQty[$sv->id_variasi] ?? null;
                if ($qty === null) continue;

                // Simulasikan penawaran riil: sedikit di atas/bawah harga beli historis.
                $harga = $isResponded
                    ? (int) round($sv->harga_beli * (1 + rand(-2, 5) / 100), -2)
                    : null;

                $item = SupplierInquiryItem::create([
                    'inquiry_id'          => $inquiry->id,
                    'id_variasi'          => $sv->id_variasi,
                    'qty'                 => $qty,
                    'harga_penawaran'     => $harga,
                    'status'              => 'pending',
                    'estimasi_pengiriman' => $isResponded ? $tglResponse->copy()->addDays(rand(2, 5)) : null,
                ]);
                $item->forceFill(['created_at' => $tglInquiry, 'updated_at' => $inquiryTs])->save();
            }
        }

        // 'selection_in_progress' realistis = sebagian needlist sudah ada progres
        // pemilihan (sebagian variasi punya pemenang), sebagian lain penawaran
        // sudah masuk tapi belum ada satu pun yang dipilih ($selectionRatio = 0).
        if ($status === 'selection_in_progress' && $selectionRatio > 0) {
            $this->markPartialSelection($needlist, $selectionRatio);
        }
    }

    private function markPartialSelection(Needlist $needlist, float $ratio): void
    {
        $itemsByVariasi = SupplierInquiryItem::whereIn('inquiry_id', $needlist->supplierInquiries()->pluck('id'))
            ->whereNotNull('harga_penawaran')
            ->get()
            ->groupBy('id_variasi');

        $variasiList = $itemsByVariasi->keys()->values();
        $sudahDipilihCount = (int) ceil($variasiList->count() * $ratio);

        foreach ($variasiList->take($sudahDipilihCount) as $idVariasi) {
            $termurah = $itemsByVariasi[$idVariasi]->sortBy('harga_penawaran')->first();
            SupplierInquiryItem::where('id', $termurah->id)->update(['status' => 'selected']);
        }
    }
}
