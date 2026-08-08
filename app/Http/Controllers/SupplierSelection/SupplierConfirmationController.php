<?php

namespace App\Http\Controllers\SupplierSelection;

use App\Models\Needlist;
use Illuminate\Http\Request;
use App\Models\SawPerhitunganDetail;
use App\Models\SawRekomendasi;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use App\Http\Controllers\Controller;
use App\Services\NeedlistSelectionGrouper;
use Illuminate\Support\Facades\Auth;

class SupplierConfirmationController extends Controller
{
    public function __construct(private NeedlistSelectionGrouper $grouper) {}

    public function saveSelection(Request $request, $needlist_id)
    {
        $needlist = Needlist::with([
            'details',
            'supplierInquiries.supplier',
            'supplierInquiries.items.variasi.m_barang',
            'supplierInquiries.items.variasi.vehicleGenerations.vehicle',
        ])->findOrFail($needlist_id);

        if ($needlist->status === 'po_issued') {
            abort(403, 'Selection tidak dapat diubah setelah PO dibuat.');
        }

        // 1. Ambil semua inquiry_id milik needlist ini
        $inquiryIds = SupplierInquiry::where('needlist_id', $needlist_id)->pluck('id');

        if ($inquiryIds->isEmpty()) {
            return back()->with('error', 'Tidak ada inquiry terkait dengan needlist ini.');
        }

        // 2. Ambil semua item dari inquiry-inquiry tersebut
        $allItems = SupplierInquiryItem::whereIn('inquiry_id', $inquiryIds)->get();

        // 3. Ambil ID item yang dicentang
        $selectedIds = $request->input('selected_items', []);

        // 4. Validasi item yang valid
        $allowedIds = $allItems->pluck('id')->toArray();
        $selectedIds = array_intersect($selectedIds, $allowedIds);

        // 5. Reset semua item → pending
        SupplierInquiryItem::whereIn('inquiry_id', $inquiryIds)
            ->update(['status' => 'pending']);

        // 6. Set item yang dipilih → selected
        if (!empty($selectedIds)) {
            SupplierInquiryItem::whereIn('id', $selectedIds)
                ->update(['status' => 'selected']);
        }

        // 6b/7. Validasi per KELOMPOK (master barang x cluster kendaraan x tier),
        //       bukan per variasi — variasi/merk berbeda dalam satu grade group
        //       adalah alternatif yang saling bersaing untuk kebutuhan yang sama,
        //       jadi maksimal 1 terpilih per kelompok, dan kelompok yang punya
        //       penawaran harus ada tepat 1 yang terpilih. Dibangun ulang lewat
        //       NeedlistSelectionGrouper (sama seperti tampilan) supaya batas
        //       kelompok konsisten dengan yang dilihat user di halaman ini.
        $groups = $this->buildGroupsForNeedlist($needlist);

        foreach ($groups as $group) {
            $rows = $group['rows'];

            $selectedInGroup = $rows->filter(
                fn ($r) => in_array($r['item']->id, $selectedIds)
            );

            if ($selectedInGroup->count() > 1) {
                SupplierInquiryItem::whereIn('inquiry_id', $inquiryIds)
                    ->update(['status' => 'pending']);
                return back()->with('error',
                    'Setiap kelompok (grade/tier) hanya boleh memilih 1 supplier. Periksa kembali pilihan Anda.'
                );
            }

            $groupPunyaPenawaran = $rows->contains(
                fn ($r) => !empty($r['item']->harga_penawaran)
            );

            if ($groupPunyaPenawaran && $selectedInGroup->isEmpty()) {
                // Rollback: kembalikan semua ke pending agar tidak setengah-setengah tersimpan
                SupplierInquiryItem::whereIn('inquiry_id', $inquiryIds)
                    ->update(['status' => 'pending']);
                return back()->with('error',
                    'Pilihan belum lengkap. Setiap kelompok harus memiliki minimal 1 supplier yang dipilih sebelum bisa disimpan.'
                );
            }
        }

        // 8. Update status needlist
        $needlist->update(['status' => 'selection_in_progress']);

        // 9. Catat otomatis kepatuhan terhadap rekomendasi SAW (ikut/override) tanpa
        //    perlu konfirmasi terpisah — dibandingkan dengan kandidat is_recommended
        //    terakhir untuk setiap variasi yang sudah dihitung.
        $this->recordSawRekomendasi($needlist->id, $inquiryIds);

        return redirect()
            ->route('pemilihan-supplier.index')
            ->with('success', 'Pilihan berhasil disimpan.');
    }

    /**
     * Bangun ulang kelompok (master barang x cluster kendaraan x tier) untuk
     * needlist ini, persis seperti yang dipakai halaman Pemilihan Supplier
     * (SupplierRecommendationController::show()), supaya validasi di sini memakai
     * batas kelompok yang sama dengan yang dilihat user.
     */
    private function buildGroupsForNeedlist(Needlist $needlist): array
    {
        $referenceVariasiIds = $needlist->details
            ->where('is_reference', true)
            ->pluck('id_variasi')
            ->toArray();

        $groupedItems = $needlist->supplierInquiries
            ->flatMap(fn ($inq) => $inq->items->map(fn ($item) => [
                'supplier' => $inq->supplier,
                'inquiry'  => $inq,
                'item'     => $item,
                'master'   => $item->variasi->m_barang,
            ]))
            ->groupBy(fn ($x) => $x['master']->id_barang);

        return $this->grouper->buildGroups($groupedItems, $referenceVariasiIds);
    }

    private function recordSawRekomendasi(int $needlistId, $inquiryIds): void
    {
        $selectedByVariasi = SupplierInquiryItem::whereIn('inquiry_id', $inquiryIds)
            ->where('status', 'selected')
            ->with('inquiry')
            ->get()
            ->groupBy('id_variasi');

        if ($selectedByVariasi->isEmpty()) {
            return;
        }

        $recommendedDetails = SawPerhitunganDetail::query()
            ->whereHas('perhitungan', fn ($q) => $q->where('needlist_id', $needlistId))
            ->where('is_recommended', 1)
            ->get()
            ->keyBy('id_variasi');

        foreach ($recommendedDetails as $idVariasi => $detail) {
            $chosenItem = $selectedByVariasi->get($idVariasi)?->first();
            if (!$chosenItem) {
                continue;
            }

            $supplierIdDipilih = $chosenItem->inquiry->supplier_id;
            $mengikuti          = (int) $detail->supplier_id === (int) $supplierIdDipilih;

            $nilaiVi = SawPerhitunganDetail::where('perhitungan_id', $detail->perhitungan_id)
                ->where('supplier_id', $supplierIdDipilih)
                ->where('id_variasi', $idVariasi)
                ->value('nilai_vi');

            SawRekomendasi::updateOrCreate(
                ['needlist_id' => $needlistId, 'id_variasi' => $idVariasi],
                [
                    'perhitungan_id'        => $detail->perhitungan_id,
                    'supplier_id_saw'       => $detail->supplier_id,
                    'supplier_id_dipilih'   => $supplierIdDipilih,
                    'mengikuti_rekomendasi' => $mengikuti ? 1 : 0,
                    'nilai_vi_terpilih'     => $nilaiVi,
                    'confirmed_at'          => now(),
                    'confirmed_by'          => Auth::id(),
                ]
            );
        }
    }
}
