<?php
namespace App\Http\Controllers\Procurement;

use App\Models\Variasi;
use App\Models\Needlist;
use Illuminate\Support\Str;
use App\Models\CartNeedlist;
use App\Models\NeedlistItem;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NeedlistController extends Controller
{
    public function index()
    {
        $needlists = Needlist::with('user')->latest()->get();
        return view('pages.procurement.needlist.index', compact('needlists'));
    }

    public function indexJson()
    {
        $query = Needlist::with('user')->orderBy('created_at', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status_label', function ($row) {
                switch ($row->status) {
                    case 'draft':
                        return '<span class="badge badge-secondary">Draf</span>';
                    case 'submitted':
                        return '<span class="badge badge-warning">Menunggu Persetujuan</span>';
                    case 'approved':
                        return '<span class="badge badge-success">Disetujui</span>';
                    case 'rejected':
                        return '<span class="badge badge-danger">Ditolak</span>';
                    case 'inquiry_created':
                        return '<span class="badge badge-primary">Konfirmasi Harga Dibuat</span>';
                    case 'po_issued':
                        return '<span class="badge badge-dark">Surat Pesanan Diterbitkan</span>';
                    case 'selection_in_progress':
                        return '<span class="badge badge-info">Pemilihan Supplier</span>';
                    case 'completed':
                        return '<span class="badge badge-success">Selesai</span>';
                    default:
                        return '-';
                }
            })
            ->addColumn('status', fn($row) => $row->status)
            ->addColumn('action', function ($row) {
                return '<a href="'.url('/needlist/'.$row->id.'/show').'"
                    class="btn btn-sm btn-outline-info">
                    <i class="fas fa-eye mr-1"></i>Detail</a>';
            })
            ->rawColumns(['status_label', 'action'])
            ->make(true);
    }

    public function supervisorIndex()
    {
        return view('pages.procurement.needlist.supervisor_index');
    }

    public function supervisorJson()
    {
        $query = Needlist::with('user')->where('status', 'submitted')->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status_label', function ($row) {
                return '<span class="badge badge-warning">Menunggu Persetujuan</span>';
            })
            ->addColumn('action', function ($row) {
                return '<a href="'.route('needlist.review', $row->id).'" class="btn btn-sm btn-primary">Review</a>';
            })
            ->rawColumns(['status_label', 'action'])
            ->make(true);
    }

    public function detailJson($id)
    {
        $needlist = Needlist::with(['details.variasi.m_barang', 'details.supplierBarang.supplier'])->findOrFail($id);

        $items = $needlist->details->map(function ($detail) {
            return [
                'master' => $detail->variasi->m_barang->nama_barang,
                'variasi' => $detail->variasi->nama_variasi,
                'qty' => $detail->qty,
                'stock' => $detail->variasi->stock,
                'harga_beli' => $detail->supplierBarang->harga_beli ?? 0,
                'supplier' => $detail->supplierBarang->supplier->nama_supplier ?? '-',
            ];
        });

        return response()->json([
            'id' => $needlist->id,
            'kode' => $needlist->kode_needlist,
            'tanggal' => $needlist->created_at->format('d/m/Y'),
            'status' => $needlist->status,
            'status_text' => ucfirst(str_replace('_', ' ', $needlist->status)),
            'catatan' => $needlist->catatan_supervisor ?? '-',
            'items' => $items
        ]);
    }


    public function toggleReference($itemId)
    {
        $item = NeedlistItem::findOrFail($itemId);
        $item->is_reference = !$item->is_reference;
        $item->save();

        return response()->json([
            'success'      => true,
            'is_reference' => $item->is_reference,
        ]);
    }

    public function getDraftItemsJson($id)
    {
        try {
            $needlist = Needlist::findOrFail($id);

            // Helper: bangun array item yang seragam dari objek Variasi
            $buildItemArray = function (array $base, Variasi $variasi): array {
                $sv = $variasi->suppliervariasi->first();
                $genIds = $variasi->vehicleGenerations->pluck('id')->toArray();
                $vehicleNames = $variasi->vehicleGenerations
                    ->map(fn($g) => $g->vehicle->name ?? '')
                    ->filter()->unique()->sort()->values()->implode(' / ');
                return array_merge($base, [
                    'barcode'         => $variasi->barcode ?? '-',
                    'nama_variasi'    => $variasi->nama_variasi ?? '-',
                    'nama_master'     => $variasi->m_barang->nama_barang ?? 'N/A',
                    'tier'            => $variasi->tier ?? null,
                    'stock'           => (int) ($variasi->stock ?? 0),
                    'harga_beli'      => (float) ($sv?->harga_beli ?? 0),
                    'nama_supplier'   => $sv?->supplier?->nama_supplier ?? '-',
                    'vehicle_gen_ids' => $genIds,
                    'vehicle_names'   => $vehicleNames,
                ]);
            };

            // 1. Approved items dari DB
            $approvedDetails = $needlist->details()
                ->where('status', 'approved')
                ->with([
                    'variasi.m_barang',
                    'variasi.suppliervariasi.supplier',
                    'variasi.vehicleGenerations.vehicle',
                ])->get();

            $approved_items = $approvedDetails->map(fn($d) => $buildItemArray([
                'id'           => $d->id,
                'detail_id'    => $d->id,
                'id_variasi'   => $d->id_variasi,
                'qty'          => $d->qty,
                'status'       => $d->status,
                'is_reference' => (bool) $d->is_reference,
                'is_approved'  => true,
            ], $d->variasi))->values()->toArray();

            // 2. Draft items dari session
            $sessionItems = session("edit_needlist_{$id}", []);
            $draft_items  = [];

            if (!empty($sessionItems)) {
                $variasis = Variasi::with([
                    'm_barang',
                    'suppliervariasi.supplier',
                    'vehicleGenerations.vehicle',
                ])->whereIn('id_variasi', collect($sessionItems)->pluck('id_variasi'))
                  ->get()->keyBy('id_variasi');

                foreach ($sessionItems as $temp) {
                    $variasi = $variasis->get($temp['id_variasi']);
                    if (!$variasi) continue;
                    $draft_items[] = $buildItemArray([
                        'detail_id'    => $temp['detail_id'],
                        'id_variasi'   => $temp['id_variasi'],
                        'qty'          => $temp['qty'],
                        'status'       => $temp['status'],
                        'is_reference' => (bool) ($temp['is_reference'] ?? false),
                        'is_approved'  => false,
                    ], $variasi);
                }
            }

            return response()->json([
                'approved_items' => $approved_items,
                'draft_items'    => $draft_items,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Needlist tidak ditemukan.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Gagal memuat item draft: ' . $e->getMessage()], 500);
        }
    }
    
    public function show($id)
{
    $needlist = Needlist::with([
        'details.variasi.m_barang.kategori',
        'details.variasi.vehicleGenerations.vehicle',
        'details.variasi.suppliervariasi.supplier',
        'details.variasi.unit',
        'supplierInquiries.supplier',
        'supplierInquiries.items.variasi.m_barang',
        'supplierInquiries.items.variasi.vehicleGenerations.vehicle',
    ])->findOrFail($id);

    $referenceVariasiIds = $needlist->details
        ->where('is_reference', true)
        ->pluck('id_variasi')
        ->toArray();

    // ===============================
    // Group Inquiry Items per Master Barang
    // ===============================
    $groupedItems = $needlist->supplierInquiries
        ->flatMap(fn ($inq) => $inq->items->map(function ($item) use ($inq) {
            return [
                'supplier' => $inq->supplier,
                'inquiry'  => $inq,
                'item'     => $item,
                'master'   => $item->variasi->m_barang
            ];
        }))
        ->groupBy(fn ($x) => $x['master']->id_barang);

    // ===============================
    // Ambil Purchase Order
    // ===============================
    $purchaseOrders = PurchaseOrder::with([
            'supplier',
            'items.variasi.m_barang'
        ])
        ->where('needlist_id', $needlist->id)
        ->get();

    return view('pages.procurement.needlist.show', compact(
        'needlist',
        'groupedItems',
        'purchaseOrders',
        'referenceVariasiIds'
    ));
}






    public function storeFromCart()
    {
        $userId = Auth::id();
        $cartItems = CartNeedlist::with('variasi')->where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            return redirect()->back()->with('error', 'Cart kosong!');
        }

        // Generate kode
        $kode = 'NL-' . now()->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $needlist = Needlist::create([
                'user_id' => $userId,
                'kode_needlist' => $kode,
                'status' => 'draft',
                'created_at' => now(),
            ]);

            foreach ($cartItems as $item) {
                NeedlistItem::create([
                    'needlist_id' => $needlist->id,
                    'id_variasi' => $item->id_variasi,
                    'qty' => $item->qty
                ]);
            }

            // Kosongkan cart
            CartNeedlist::where('user_id', $userId)->delete();

            DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Gagal membuat Needlist: ' . $e->getMessage());
            }


        return redirect()->route('needlist.show', $needlist->id)
            ->with('success', 'Needlist berhasil dibuat dalam status Draft! Silahkan lengkapi dan submit.');
    }

    // /**
    //  * PENTING: Method ini (submit) sudah digantikan fungsinya oleh 'update' dengan action_type=submit
    //  * dan 'submitReview'. Bisa dihapus jika alur edit/update sudah diadopsi penuh.
    //  */
    // public function submit($id)
    // {
    //     $needlist = Needlist::where('status', 'draft')->findOrFail($id);

    //     $needlist->update([
    //         'status' => 'submitted',
    //         'submitted_by' => Auth::id(),
    //         'submitted_at' => now(),
    //     ]);

    //     return redirect()->route('needlist.index', $id)
    //         ->with('success', 'Needlist berhasil diajukan ke supervisor!');
    // }

    public function submitted()
    {
        $needlists = Needlist::with('user')->where('status', 'submitted')->get();

        return view('pages.procurement.needlist.submitted', compact('needlists'));
    }

    public function loadReview($id)
    {
        // Data tampilan (barcode, variasi, stock, harga, supplier) selalu
        // dihitung fresh dari DB — hanya status approve/reject yang di-draft
        // sementara di session, supaya tidak ada data basi yang ter-cache.
        $needlist = Needlist::with(['details.variasi.m_barang', 'details.supplierBarang.supplier'])
            ->where('status', 'submitted')
            ->findOrFail($id);

        $sessionKey = "review_needlist_{$id}";
        $draftStatus = session()->get($sessionKey, []);

        return view('pages.procurement.needlist.review', compact('needlist', 'draftStatus'));
    }



    public function edit($id) {
        $needlist = Needlist::with([
            'details.variasi.m_barang', 
            'details.supplierBarang.supplier',
            'details'
        ])
        ->whereIn('status', ['draft', 'rejected'])
        ->findOrFail($id);
        
        // Ambil semua item yang statusnya TIDAK SAMA DENGAN 'approved'
        $temp_items = $needlist->details
            ->where('status', '!=', 'approved')
            ->map(function ($d) {
                return [
                    'detail_id'    => $d->id,
                    'id_variasi'   => $d->id_variasi,
                    'qty'          => $d->qty,
                    'status'       => $d->status,
                    'is_reference' => (bool) $d->is_reference,
                ];
            })->values()->all();

        session(["edit_needlist_{$id}" => $temp_items]);
        
        return view('pages.procurement.needlist.edit', compact('needlist'));
    }


    public function update(Request $request, $id)
{
    $request->validate([
        'temp_items_json' => 'required|string',
        'action_type' => 'required|in:save,submit'
    ]);

    $needlist = Needlist::whereIn('status', ['draft', 'rejected'])->findOrFail($id);
    $actionType = $request->input('action_type');
    $message = 'Perubahan Needlist berhasil disimpan.';

    // if ($request->has('catatan')) {
    //     $needlist->catatan = $request->catatan;
    // }

    // Ambil data dari session (draft edit)
    $sessionKey = "edit_needlist_{$id}";
    $draftDetails = session()->get($sessionKey, []);

    // ✅ Parse JSON items yang dikirim JS
    $parsedItems = json_decode($request->input('temp_items_json'), true) ?? [];

    DB::transaction(function () use ($needlist, $actionType, &$message, $draftDetails, $parsedItems) {

        // === 1️⃣ Update qty item lama yang belum approved ===
        foreach ($parsedItems as $item) {
            if (!empty($item['detail_id'])) {
                $detail = NeedlistItem::where('needlist_id', $needlist->id)
                    ->where('id', $item['detail_id'])
                    ->where('status', '!=', 'approved')
                    ->first();

                if ($detail) {
                    $detail->qty          = (int)$item['qty'];
                    $detail->is_reference = (bool)($item['is_reference'] ?? false);
                    $detail->save();
                }
            }
        }

        // === 2️⃣ Simpan item baru dari session (detail_id null) ===
        foreach ($parsedItems as $item) {
            if (empty($item['detail_id'])) {
                $exists = NeedlistItem::where('needlist_id', $needlist->id)
                    ->where('id_variasi', $item['id_variasi'])
                    ->exists();

                if (!$exists) {
                    NeedlistItem::create([
                        'needlist_id'  => $needlist->id,
                        'id_variasi'   => $item['id_variasi'],
                        'qty'          => $item['qty'],
                        'status'       => $item['status'] ?? 'pending',
                        'is_reference' => (bool)($item['is_reference'] ?? false),
                    ]);
                }
            }
        }

        // === 3️⃣ Hapus item dari DB yang tidak ada di draft lagi ===
        $sessionVariasiIds = collect($parsedItems)->pluck('id_variasi')->toArray();
        $itemsToDelete = NeedlistItem::where('needlist_id', $needlist->id)
            ->whereNotIn('id_variasi', $sessionVariasiIds)
            ->where('status', '!=', 'approved')
            ->get();

        foreach ($itemsToDelete as $deletedItem) {
            $deletedItem->delete();
        }

        // === 4️⃣ Tentukan status Needlist ===
        if ($actionType === 'submit') {
            $needlist->status = 'submitted';
            // $needlist->submitted_by = Auth::id();
            // $needlist->submitted_at = now();
            $message = 'Needlist berhasil diajukan ke supervisor!';
        } elseif ($needlist->status == 'rejected') {
            $needlist->status = 'draft';
        }

        $needlist->save();
    });

    // === 5️⃣ Hapus session setelah update ===
    session()->forget("edit_needlist_{$id}");

    return redirect()->route('needlist.show', $needlist->id)
        ->with('success', $message);
}



    public function addDraftDetail(Request $request, $id)
{
    $request->validate([
        'id_variasi' => 'required|exists:variasis,id_variasi',
    ]);

    $needlist = Needlist::whereIn('status', ['draft', 'rejected'])->findOrFail($id);

    $sessionKey = "edit_needlist_{$id}";
    $draftItems = session()->get($sessionKey, []);

    // Cegah duplikasi
    $exists = collect($draftItems)->contains(fn($i) => $i['id_variasi'] == $request->id_variasi);
    if ($exists) {
        return response()->json(['error' => 'Barang sudah ada di Needlist.'], 422);
    }

    // Ambil data variasi lengkap
    $variasi = Variasi::with(['m_barang', 'suppliervariasi.supplier', 'vehicleGenerations.vehicle'])
        ->where('id_variasi', $request->id_variasi)
        ->firstOrFail();

    $supplierVariasi = $variasi->suppliervariasi->first();
    $genIds = $variasi->vehicleGenerations->pluck('id')->toArray();
    $vehicleNames = $variasi->vehicleGenerations
        ->map(fn($g) => $g->vehicle->name ?? '')
        ->filter()->unique()->sort()->values()->implode(' / ');

    // Tambahkan item baru ke session
    $newItem = [
        'detail_id'       => null,
        'id_variasi'      => $variasi->id_variasi,
        'barcode'         => $variasi->barcode,
        'nama_master'     => $variasi->m_barang->nama_barang ?? '-',
        'nama_variasi'    => $variasi->nama_variasi ?? '-',
        'nama_supplier'   => $supplierVariasi?->supplier?->nama_supplier ?? '-',
        'harga_beli'      => $supplierVariasi?->harga_beli ?? 0,
        'stock'           => $variasi->stock ?? 0,
        'tier'            => $variasi->tier ?? null,
        'vehicle_gen_ids' => $genIds,
        'vehicle_names'   => $vehicleNames,
        'qty'             => 1,
        'status'          => 'pending',
        'is_reference'    => false,
        'is_approved'     => false,
    ];

    $draftItems[] = $newItem;
    session()->put($sessionKey, $draftItems);

    return response()->json(['success' => true, 'item' => $newItem]);
}



    public function removeDraftDetail(Request $request, $id)
    {
        

        $needlist = Needlist::whereIn('status', ['draft', 'rejected'])->findOrFail($id);
        $sessionKey = "edit_needlist_{$id}";
        $draftItems = session()->get($sessionKey, []);

        // Filter keluar item yang ingin dihapus
        $updatedItems = collect($draftItems)
            ->reject(fn($i) => $i['id_variasi'] == $request->id_variasi)
            ->values()
            ->toArray();

        session()->put($sessionKey, $updatedItems);

        return response()->json(['success' => true, 'message' => 'Item berhasil dihapus dari Needlist.']);
    }


    
    // Ajax untuk menambahkan item baru ke Needlist
    public function storeDetail(Request $request)
    {
        $request->validate([
            'id_variasi' => 'required|exists:variasis,id_variasi',
            'needlist_id' => 'required|exists:needlists,id',
        ]);

        $exists = NeedlistItem::where('needlist_id', $request->needlist_id)
            ->where('id_variasi', $request->id_variasi)
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Barang sudah ada dalam Needlist.'], 422);
        }

        // Simpan langsung ke database
        NeedlistItem::create([
            'needlist_id' => $request->needlist_id,
            'id_variasi' => $request->id_variasi,
            'qty' => 1,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true]);
    }

    // Ajax/Form untuk menghapus item dari Needlist
    public function destroyDetail(Request $request)
    {
        $detail = NeedlistItem::findOrFail($request->detail_id);

        if ($detail->status === 'approved') {
            return back()->with('error', 'Item yang sudah di-approve tidak dapat dihapus.');
        }

        $detail->delete();

        return back()->with('success', 'Item berhasil dihapus.');
    }
    
    public function submit($id)
    {
        $needlist = Needlist::whereIn('status', ['draft', 'rejected'])->findOrFail($id);
        
        if ($needlist->details()->where('status', '!=', 'approved')->doesntExist()) {
             return back()->with('error', 'Needlist tidak memiliki item yang dapat diajukan.')->withInput();
        }

        $needlist->update([
            'status' => 'submitted',
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
        ]);

        return redirect()->route('needlist.show', $needlist->id)->with('success', 'Needlist berhasil diajukan ke supervisor.');
    }
    
    

    public function approveTemp($id, Request $request)
    {
        $detailId = (int) $request->detail_id;

        $exists = NeedlistItem::where('needlist_id', $id)->where('id', $detailId)->exists();
        if (!$exists) return back()->with('error', 'Item tidak ditemukan.');

        $sessionKey = "review_needlist_{$id}";
        $draftStatus = session()->get($sessionKey, []);

        $draftStatus[$detailId] = ['status' => 'approved', 'rejected_reason' => null];
        session()->put($sessionKey, $draftStatus);

        return back()->with('success', 'Item disetujui (sementara).');
    }

    public function rejectTemp($id, Request $request)
    {
        $request->validate([
            'detail_id' => 'required',
            'rejected_reason' => 'required|string|max:255'
        ]);

        $detailId = (int) $request->detail_id;

        $exists = NeedlistItem::where('needlist_id', $id)->where('id', $detailId)->exists();
        if (!$exists) return back()->with('error', 'Item tidak ditemukan.');

        $sessionKey = "review_needlist_{$id}";
        $draftStatus = session()->get($sessionKey, []);

        $draftStatus[$detailId] = ['status' => 'rejected', 'rejected_reason' => $request->rejected_reason];
        session()->put($sessionKey, $draftStatus);

        return back()->with('info', 'Item ditolak (sementara).');
    }


    public function submitReview($id)
    {
        $needlist = Needlist::with('details')->where('status', 'submitted')->findOrFail($id);
        $sessionKey = "review_needlist_{$id}";
        $draftStatus = session()->get($sessionKey, []);

        if (empty($draftStatus)) {
            return back()->with('error', 'Tidak ada data review untuk disimpan.');
        }

        $finalStatuses = $needlist->details->map(
            fn($d) => $draftStatus[$d->id]['status'] ?? $d->status
        );

        if (!$finalStatuses->every(fn($s) => in_array($s, ['approved', 'rejected']))) {
            return back()->with('error', 'Masih ada item yang belum direview.');
        }

        DB::transaction(function () use ($needlist, $draftStatus, $finalStatuses) {
            foreach ($needlist->details as $detail) {
                $draft = $draftStatus[$detail->id] ?? null;
                if ($draft) {
                    $detail->status = $draft['status'];
                    $detail->rejected_reason = $draft['rejected_reason'];
                    $detail->save();
                }
            }

            if ($finalStatuses->every(fn($s) => $s === 'approved')) {
                $needlist->status = 'approved';
                $needlist->approved_by = Auth::id();
                $needlist->approved_at = now();
            } else {
                $needlist->status = 'rejected';
            }

            $needlist->save();
        });

        session()->forget($sessionKey);

        return redirect()->route('needlist.supervisorIndex')->with('success', 'Hasil review berhasil disimpan.');
    }

}