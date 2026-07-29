<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\MBarang;
use App\Models\Variasi;
use App\Models\Kategori;
use App\Models\Supplier;
use App\Models\SupplierVariasi;
use App\Models\VehicleGeneration;
use App\Models\ProductVariantCompatibility;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VariasiController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Variasi::with(['m_barang.kategori', 'supplierVariasi.supplier','unit'])->select('variasis.*');
            
            return DataTables::of($data)
                ->addColumn('nama_barang', fn($r) => $r->m_barang->nama_barang ?? '-')
                ->addColumn('nama_variasi', fn($r) => $r->nama_variasi ?? '-')
                ->addColumn('kategori', fn($r) => $r->m_barang->kategori->nama_kategori ?? '-')
                ->addColumn('nama_unit', fn($r) => $r->unit->nama_unit ?? '-')
                ->addColumn('supplier', function($r) {
                    $s = $r->supplierVariasi->pluck('supplier.nama_supplier')->filter()->unique()->implode(', ');
                    return $s ?: '-';
                })
                ->addColumn('harga_jual', fn($r) => $r->harga_jual)
                ->addColumn('is_active', fn($r) => $r->is_active
                    ? '<span class="badge badge-success">Aktif</span>'
                    : '<span class="badge badge-secondary">Nonaktif</span>')
                ->addColumn('action', fn($r) =>
                    '<a href="'.route('barang.edit', $r->id_variasi).'" class="btn btn-warning btn-xs btn-icon-xs mr-1"><i class="fas fa-edit"></i></a>'
                    .'<button class="btn btn-danger btn-xs btn-icon-xs delete-btn" data-id="'.$r->id_variasi.'"><i class="fas fa-trash"></i></button>')
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return view('pages.variasi.manage-index');
    }
    
    /**
     * Menampilkan form untuk membuat variasi produk baru.
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $m_barangs = MBarang::all();
        $suppliers = Supplier::all();
        $units     = Unit::all();
        $kategoris = Kategori::all();

        $vehicleGenerations = VehicleGeneration::with('vehicle')->get()
            ->groupBy(fn($g) => $g->vehicle->manufacturer ?? 'Lainnya')
            ->map(fn($gens) => $gens->groupBy(fn($g) => $g->vehicle->name ?? 'Unknown'));

        $selectedGenerations = [];

        $lastProduk  = Variasi::orderBy('id_variasi', 'desc')->first();
        $nextBarcode = $lastProduk ? str_pad((int)$lastProduk->barcode + 1, 5, '0', STR_PAD_LEFT) : '00001';

        return view('pages.variasi.form', compact('m_barangs', 'suppliers', 'units', 'kategoris', 'nextBarcode', 'vehicleGenerations', 'selectedGenerations'));
    }

    /**
     * Menyimpan variasi produk baru.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->normalizeRupiahInputs($request);

        $validator = Validator::make($request->all(), [
            'id_barang' => 'required|exists:m_barangs,id_barang',
            'barcode' => 'required|string|unique:variasis|max:255',
            'nama_variasi' => 'required|max:100',
            'id_unit' => 'required|exists:units,id_unit',
            'harga_jual' => 'required|numeric|min:0',
            // 'stock' => 'required|integer|min:0',
            'supplier_data' => 'required|array|min:1',
            'supplier_data.*.id_supplier' => 'required|exists:suppliers,id_supplier|distinct',
            'supplier_data.*.harga_beli' => 'required|numeric|min:0',
            // 'supplier_data.*.jarak' => 'nullable|integer|min:0',
            // 'supplier_data.*.waktu_pengiriman' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $hargaJual = (int) str_replace(['.', ','], '', $request->harga_jual);

            $produkVariasi = Variasi::create([
                'id_barang'   => $request->id_barang,
                'barcode'     => $request->barcode,
                'nama_variasi'=> $request->nama_variasi,
                'id_unit'     => $request->id_unit,
                'harga_jual'  => $hargaJual,
                'stock'       => 0,
                'part_number' => $request->part_number,
                'is_active'   => $request->boolean('is_active', true),
                'tier'        => $request->tier ?: null,
            ]);

            foreach ($request->supplier_data as $supplier) {
                SupplierVariasi::create([
                    'id_variasi'  => $produkVariasi->id_variasi,
                    'id_supplier' => $supplier['id_supplier'],
                    'harga_beli'  => (int) str_replace(['.', ','], '', $supplier['harga_beli'] ?? 0),
                    'harga_list'  => $supplier['harga_list'] ? (int) str_replace(['.', ','], '', $supplier['harga_list']) : null,
                    'kode_list'   => $supplier['kode_list'] ?? null,
                    'kode_beli'   => $supplier['kode_beli'] ?? null,
                    'diskon'      => $supplier['diskon'] ?? 0,
                ]);
            }

            if ($request->filled('vehicle_generation_ids')) {
                foreach ($request->vehicle_generation_ids as $genId) {
                    ProductVariantCompatibility::create([
                        'id_variasi'          => $produkVariasi->id_variasi,
                        'vehicle_generation_id'=> $genId,
                        'is_compatible'        => true,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('barang.index')->with('success', 'Produk berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $variasi = Variasi::with(['suppliervariasi.supplier', 'vehicleGenerations.vehicle'])->findOrFail($id);
        $m_barangs = MBarang::all();
        $suppliers = Supplier::all();
        $units     = Unit::all();
        $kategoris = Kategori::all();

        $vehicleGenerations = VehicleGeneration::with('vehicle')->get()
            ->groupBy(fn($g) => $g->vehicle->manufacturer ?? 'Lainnya')
            ->map(fn($gens) => $gens->groupBy(fn($g) => $g->vehicle->name ?? 'Unknown'));

        $selectedGenerations = $variasi->vehicleGenerations->pluck('id')->toArray();

        return view('pages.variasi.form', compact(
            'variasi', 'm_barangs', 'suppliers', 'units', 'kategoris', 'vehicleGenerations', 'selectedGenerations'
        ));
    }

    public function update(Request $request, $id)
    {
        $variasi = Variasi::findOrFail($id);

        $this->normalizeRupiahInputs($request);

        $validator = Validator::make($request->all(), [
            'id_barang'    => 'required|exists:m_barangs,id_barang',
            'barcode'      => 'required|string|max:255|unique:variasis,barcode,'.$variasi->id_variasi.',id_variasi',
            'nama_variasi' => 'required|max:100',
            'id_unit'      => 'required|exists:units,id_unit',
            'harga_jual'   => 'required|numeric|min:0',
            'supplier_data' => 'required|array|min:1',
            'supplier_data.*.id_supplier' => 'required|exists:suppliers,id_supplier|distinct',
            'supplier_data.*.harga_beli' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $hargaJual = (int) str_replace(['.', ','], '', $request->harga_jual);

            $variasi->update([
                'id_barang'    => $request->id_barang,
                'barcode'      => $request->barcode,
                'nama_variasi' => $request->nama_variasi,
                'id_unit'      => $request->id_unit,
                'harga_jual'   => $hargaJual,
                'part_number'  => $request->part_number,
                'is_active'    => $request->boolean('is_active', true),
                'tier'         => $request->tier ?: null,
            ]);

            if ($request->has('supplier_data')) {
                $variasi->suppliervariasi()->delete();
                foreach ($request->supplier_data as $supplier) {
                    SupplierVariasi::create([
                        'id_variasi'  => $variasi->id_variasi,
                        'id_supplier' => $supplier['id_supplier'],
                        'harga_beli'  => (int) str_replace(['.', ','], '', $supplier['harga_beli'] ?? 0),
                        'harga_list'  => $supplier['harga_list'] ? (int) str_replace(['.', ','], '', $supplier['harga_list']) : null,
                        'kode_list'   => $supplier['kode_list'] ?? null,
                        'kode_beli'   => $supplier['kode_beli'] ?? null,
                        'diskon'      => $supplier['diskon'] ?? 0,
                    ]);
                }
            }

            $variasi->compatibilities()->delete();
            if ($request->filled('vehicle_generation_ids')) {
                foreach ($request->vehicle_generation_ids as $genId) {
                    ProductVariantCompatibility::create([
                        'id_variasi'           => $variasi->id_variasi,
                        'vehicle_generation_id' => $genId,
                        'is_compatible'         => true,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('barang.index')->with('success', 'Variasi berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui data: '.$e->getMessage());
        }
    }

    /**
     * Bersihkan pemisah ribuan (titik/koma) dari input harga sebelum divalidasi.
     * Input dikirim sudah terformat ala Indonesia (mis. "1.450.000") oleh JS form,
     * sehingga rule `numeric` akan gagal untuk nilai >= 1 juta (dua titik atau lebih)
     * kalau dibiarkan apa adanya.
     */
    private function normalizeRupiahInputs(Request $request): void
    {
        $clean = fn ($v) => $v === null || $v === '' ? $v : str_replace(['.', ','], '', $v);

        $supplierData = collect($request->input('supplier_data', []))->map(function ($s) use ($clean) {
            $s['harga_beli'] = $clean($s['harga_beli'] ?? null);
            $s['harga_list'] = $clean($s['harga_list'] ?? null);
            return $s;
        })->toArray();

        $request->merge([
            'harga_jual' => $clean($request->input('harga_jual')),
            'supplier_data' => $supplierData,
        ]);
    }

    /**
     * Mencari variasi produk berdasarkan barcode.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cariByBarcode(Request $request)
    {
        $barcode = $request->barcode;
        
        $produk = ProdukVariasi::with('masterBarang')->where('barcode', $barcode)->first();

        if (!$produk) {
            return response()->json(['status' => 'error', 'message' => 'Barang tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $produk->id_variasi,
                'nama' => $produk->nama_variasi,
                'nama_barang' => $produk->Barang->nama_barang,
                'harga_jual' => $produk->harga_jual,
                'stock' => $produk->stock
            ]
        ]);
    }


public function createMultiple()
    {
        $kategoris = Kategori::all();
        $suppliers = Supplier::all();
        $m_barangs = MBarang::all();

        // Generate nextBarcode
        $lastBarang = Variasi::orderBy('id_variasi', 'desc')->first();
        $nextBarcode = $lastBarang ? str_pad((int)$lastBarang->barcode + 1, 5, '0', STR_PAD_LEFT) : '00001';

        return view('pages.variasi.form_multiple', compact('kategoris', 'suppliers', 'm_barangs', 'nextBarcode'));
    }

    public function storeMultiple(Request $request)
    {
        $request->validate([
            'barangs.*.barcode' => 'required|unique:barangs|max:10',
            'barangs.*.nama_barang' => 'required|max:100',
            'barangs.*.id_kategori' => 'required|exists:kategoris,id_kategori',
            'barangs.*.id_supplier' => 'required|exists:suppliers,id_supplier',
            'barangs.*.id_barang' => 'required|exists:m_barangs,id_barang',
            'barangs.*.harga_jual' => 'required|numeric|min:0',
        ]);

        foreach ($request->barangs as $barangData) {
            $barangData['harga_jual'] = str_replace('.', '', $barangData['harga_jual']);
            Barang::create($barangData);
        }

        return redirect()->route('barang.index')->with('success', 'Beberapa barang berhasil ditambahkan.');
    }

    // ================================================================
    // SKU MANAGEMENT — DataTables + Detail Modal
    // ================================================================

    public function skuIndex()
    {
        return view('pages.variasi.index');
    }

    public function skuIndexTerkategori()
    {
        return view('pages.variasi.index-terkategori');
    }

    public function datatableJson(Request $request)
    {
        $draw     = $request->input('draw', 1);
        $start    = $request->input('start', 0);
        $length   = $request->input('length', 10);
        $search   = trim($request->input('search.value', ''));
        $orderCol = (int) $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $columnMap = [
            1 => 'variasis.barcode',
            2 => 'm_barangs.nama_barang',
            5 => 'variasis.stock',      // modal picker: stock di kolom 5
            6 => 'variasis.harga_jual',
            7 => 'variasis.stock',      // SKU page: stock di kolom 7
        ];
        $orderColumn = $columnMap[$orderCol] ?? 'variasis.barcode';

        $total = Variasi::count();

        $query = Variasi::with([
            'm_barang.kategori',
            'unit',
            'suppliervariasi.supplier',
            'vehicleGenerations.vehicle',
        ])
        ->join('m_barangs', 'variasis.id_barang', '=', 'm_barangs.id_barang')
        ->select('variasis.*');

        if ($request->boolean('low_stock_only') && $search === '') {
            $query->where('variasis.stock', '<=', 5);
        }

        if ($request->boolean('terkategori_only')) {
            $query->where('m_barangs.nama_barang', 'not like', '%belum dikategorikan%');
        }

        if ($search !== '') {
            $keywords = array_filter(array_map('trim', explode(' ', $search)));
            foreach ($keywords as $kw) {
                $query->where(function ($q) use ($kw) {
                    $q->where('variasis.nama_variasi', 'like', "%$kw%")
                      ->orWhere('variasis.part_number', 'like', "%$kw%")
                      ->orWhere('variasis.barcode', 'like', "%$kw%")
                      ->orWhere('m_barangs.nama_barang', 'like', "%$kw%")
                      ->orWhereHas('suppliervariasi.supplier', fn($q) =>
                            $q->where('nama_supplier', 'like', "%$kw%"))
                      ->orWhereHas('suppliervariasi', fn($q) =>
                            $q->where('kode_beli', 'like', "%$kw%")->orWhere('kode_list', 'like', "%$kw%"))
                      ->orWhereHas('vehicleGenerations', fn($q) =>
                            $q->where('code', 'like', "%$kw%")->orWhere('nickname', 'like', "%$kw%"))
                      ->orWhereHas('vehicleGenerations.vehicle', fn($q) =>
                            $q->where('name', 'like', "%$kw%"));
                });
            }
        }

        $filtered = $query->count();
        $items    = $query->orderBy($orderColumn, $orderDir)->skip($start)->take($length)->get();

        $data = $items->map(function ($variasi) {
            $barang  = $variasi->m_barang ?? null;
            $svList  = $variasi->suppliervariasi;

            $gens = $variasi->vehicleGenerations;
            $vehicleStr = $gens->map(fn($g) => ($g->vehicle->name ?? '') . ' ' . $g->code)
                              ->filter()->take(3)->implode(' / ');
            if ($gens->count() > 3) $vehicleStr .= ' ...';

            $suppliers = $svList->map(fn($sv) => [
                'name'       => $sv->supplier->nama_supplier ?? '-',
                'kode_beli'  => $sv->kode_beli ?? '-',
                'harga_beli' => (int) ($sv->harga_beli ?? 0),
            ])->values();

            $hargaBeliMin = (int) ($svList->min('harga_beli') ?? 0);
            $hargaBeliMax = (int) ($svList->max('harga_beli') ?? 0);

            return [
                'id_variasi'     => $variasi->id_variasi,
                'barcode'        => $variasi->barcode ?? '-',
                'nama_barang'    => $barang->nama_barang ?? '-',
                'nama_variasi'   => $variasi->nama_variasi ?? '-',
                'vehicle'        => $vehicleStr ?: '-',
                'part_number'    => $variasi->part_number ?? '-',
                'suppliers'      => $suppliers,
                'harga_beli_min' => $hargaBeliMin,
                'harga_beli_max' => $hargaBeliMax,
                'harga_jual'     => (int) ($variasi->harga_jual ?? 0),
                'stock'          => (int) ($variasi->stock ?? 0),
                'tier'           => $variasi->tier ?? null,
                'is_active'      => (bool) ($variasi->is_active ?? true),
            ];
        });

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    public function detail($id)
    {
        $variasi = Variasi::with([
            'm_barang.kategori',
            'unit',
            'suppliervariasi.supplier',
            'vehicleGenerations.vehicle',
        ])->findOrFail($id);

        $barang = $variasi->m_barang;
        $unit   = $variasi->unit;

        $penggunaanMobil = $variasi->vehicleGenerations->map(function ($gen) {
            return [
                'vehicle_name'        => $gen->vehicle->name ?? '-',
                'generation_code'     => $gen->code ?? '-',
                'generation_name'     => $gen->nickname ?? '',
                'start_year'          => $gen->start_year,
                'end_year'            => $gen->end_year,
                'compatibility_notes' => $gen->pivot->compatibility_notes ?? null,
            ];
        })->values();

        $supplierData = $variasi->suppliervariasi->map(fn($sv) => [
            'id'              => $sv->supplier->id_supplier ?? null,
            'code'            => $sv->supplier->kode_supplier ?? '-',
            'name'            => $sv->supplier->nama_supplier ?? '-',
            'harga_beli_netto'=> (int) ($sv->harga_beli ?? 0),
            'cost_code'       => $sv->kode_beli ?? '-',
            'harga_list'      => (int) ($sv->harga_list ?? 0),
            'harga_list_code' => $sv->kode_list ?? '-',
        ])->values();

        return response()->json([
            'barcode' => $variasi->barcode ?? '-',
            'master_barang' => [
                'id'       => $barang->id_barang ?? null,
                'name'     => $barang->nama_barang ?? '-',
                'category' => $barang->kategori->nama_kategori ?? '-',
                'unit'     => [
                    'name' => $unit->nama_unit ?? '-',
                    'code' => $unit->kode_unit ?? '-',
                ],
            ],
            'variasi' => [
                'id'          => $variasi->id_variasi,
                'brand_name'  => $variasi->nama_variasi ?? '-',
                'part_number' => $variasi->part_number ?? '-',
                'tier'        => $variasi->tier ?? null,
            ],
            'penggunaan_mobil' => $penggunaanMobil,
            'suppliers'        => $supplierData,
            'harga_jual'       => (int) ($variasi->harga_jual ?? 0),
            'stock'            => (int) ($variasi->stock ?? 0),
            'is_active'        => (bool) ($variasi->is_active ?? true),
        ]);
    }

}