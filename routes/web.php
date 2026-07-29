<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ====== Controllers ======
use App\Http\Controllers\PosController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\VariasiController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PenerimaanController;
use App\Http\Controllers\MasterBarangController;
use App\Http\Controllers\DuplicateItemController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleGenerationController;
use App\Http\Controllers\Procurement\ReceiptController;
use App\Http\Controllers\Procurement\NeedlistController;
use App\Http\Controllers\Procurement\CartNeedlistController;
use App\Http\Controllers\Procurement\PurchaseOrderController;
use App\Http\Controllers\Procurement\SupplierInquiryController;
use App\Http\Controllers\SupplierSelection\SupplierConfirmationController;
use App\Http\Controllers\SupplierSelection\SawHistorisController;
use App\Http\Controllers\SupplierSelection\SawKriteriaController;
use App\Http\Controllers\Procurement\SupplierSelectionSawController;
use App\Http\Controllers\SupplierSelection\SupplierRecommendationController;

// =======================================================
// PUBLIC & AUTH ROUTES
// =======================================================
Route::get('/', fn () => redirect('/login'));
Auth::routes(); // login, register, password reset, etc.
Route::get('/dashboard', [HomeController::class, 'index'])
    ->name('dashboard')
    ->middleware('auth');
// =======================================================
// AUTHENTICATED ROUTES
// =======================================================
Route::middleware(['auth'])->group(function () {

    // ===================================================
    // MANAJEMEN USER (owner & admin only)
    // ===================================================
    Route::resource('users', UserController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('admin_or_owner');

    // ===================================================
    // MASTER DATA
    // ===================================================
    Route::resources([
        'kategori'   => KategoriController::class,
        'supplier'   => SupplierController::class,
        'unit'       => UnitController::class,
        'pelanggan'  => PelangganController::class,
    ]);

    // ===================================================
    // KENDARAAN & GENERASI
    // ===================================================
    Route::prefix('kendaraan')->name('kendaraan.')->group(function () {
        Route::get('/',                    [VehicleController::class, 'index'])->name('index');
        Route::get('/create',              [VehicleController::class, 'create'])->name('create');
        Route::post('/',                   [VehicleController::class, 'store'])->name('store');
        Route::get('/{kendaraan}/edit',    [VehicleController::class, 'edit'])->name('edit');
        Route::put('/{kendaraan}',         [VehicleController::class, 'update'])->name('update');
        Route::delete('/{kendaraan}',      [VehicleController::class, 'destroy'])->name('destroy');

        Route::prefix('/{kendaraan}/generasi')->name('generasi.')->group(function () {
            Route::get('/',                [VehicleGenerationController::class, 'index'])->name('index');
            Route::get('/create',          [VehicleGenerationController::class, 'create'])->name('create');
            Route::post('/',               [VehicleGenerationController::class, 'store'])->name('store');
            Route::get('/{generasi}/edit', [VehicleGenerationController::class, 'edit'])->name('edit');
            Route::put('/{generasi}',      [VehicleGenerationController::class, 'update'])->name('update');
            Route::delete('/{generasi}',   [VehicleGenerationController::class, 'destroy'])->name('destroy');
        });
    });

    // ===================================================
    // MASTER BARANG
    // ===================================================
    Route::prefix('m_barang')->name('m_barang.')->group(function () {
        Route::get('/', [MasterBarangController::class, 'index'])->name('index');
        Route::get('/create', [MasterBarangController::class, 'create'])->name('create');
        Route::post('/store', [MasterBarangController::class, 'store'])->name('store');
        Route::get('/create-multiple', [MasterBarangController::class, 'createMultiple'])->name('createMultiple');
        Route::post('/store-multiple', [MasterBarangController::class, 'storeMultiple'])->name('storeMultiple');
        Route::get('/{id_barang}/edit', [MasterBarangController::class, 'edit'])->name('edit');
        Route::put('/{id_barang}/update', [MasterBarangController::class, 'update'])->name('update');
        Route::delete('/{id_barang}/destroy', [MasterBarangController::class, 'destroy'])->name('destroy');
        Route::get('/cari-by-barcode', [MasterBarangController::class, 'cariByBarcode'])->name('cariByBarcode');
    });

    // ===================================================
    // SKU MANAGEMENT — DataTables + Detail Modal
    // ===================================================
    Route::prefix('variasi')->name('variasi.')->group(function () {
        Route::get('/',              [VariasiController::class, 'skuIndex'])->name('index');
        Route::get('/terkategori',   [VariasiController::class, 'skuIndexTerkategori'])->name('index.terkategori');
        Route::post('/datatable',    [VariasiController::class, 'datatableJson'])->name('datatable');
        Route::get('/{id}/detail',   [VariasiController::class, 'detail'])->name('detail');
    });

    // ===================================================
    // BARANG VARIASI
    // ===================================================
    Route::prefix('barang')->name('barang.')->group(function () {
        Route::get('/', [VariasiController::class, 'index'])->name('index');
        Route::get('/create', [VariasiController::class, 'create'])->name('create');
        Route::post('/store', [VariasiController::class, 'store'])->name('store');
        Route::get('/create-multiple', [VariasiController::class, 'createMultiple'])->name('createMultiple');
        Route::post('/store-multiple', [VariasiController::class, 'storeMultiple'])->name('storeMultiple');
        Route::get('/{id_variasi}/edit', [VariasiController::class, 'edit'])->name('edit');
        Route::put('/{id_variasi}/update', [VariasiController::class, 'update'])->name('update');
        Route::delete('/{id_variasi}/destroy', [VariasiController::class, 'destroy'])->name('destroy');
        Route::get('/cari-by-barcode', [VariasiController::class, 'cariByBarcode'])->name('cariByBarcode');
    });

    // ===================================================
    // DETEKSI & MERGE ITEM DUPLIKAT
    // ===================================================
    Route::prefix('duplikat-item')->name('duplikat-item.')->group(function () {
        Route::get('/', [DuplicateItemController::class, 'index'])->name('index');
        Route::post('/terapkan', [DuplicateItemController::class, 'terapkanGrup'])->name('terapkan');
        Route::get('/riwayat', [DuplicateItemController::class, 'riwayat'])->name('riwayat');
        Route::get('/riwayat-kategorisasi', [DuplicateItemController::class, 'riwayatKategorisasi'])->name('riwayat-kategorisasi');
    });

    // ===================================================
    // BARCODE
    // ===================================================
    Route::prefix('barcode')->name('barcode.')->group(function () {
        Route::get('/generate', [VariasiController::class, 'generateBarcode'])->name('generate');
        Route::get('/print/multiple', [BarcodeController::class, 'printMultiple'])->name('print.multiple');
        Route::get('/print/template', [BarcodeController::class, 'printTemplate107'])->name('print.template');
        Route::get('/print/template/101', [BarcodeController::class, 'printTemplate101'])->name('print.template.101');
        Route::get('/print/template/fanbelt', [BarcodeController::class, 'printTemplateFanbelt'])->name('print.template.fanbelt');
    });

    // ===================================================
    // PENERIMAAN
    // ===================================================
    Route::prefix('penerimaan')->name('penerimaan.')->group(function () {
        Route::get('/', [PenerimaanController::class, 'index'])->name('index');
        Route::get('/create', [PenerimaanController::class, 'create'])->name('create');
        Route::post('/store', [PenerimaanController::class, 'store'])->name('store');
        Route::get('/{id}/detail', [PenerimaanController::class, 'show'])->name('show');
        Route::get('/get-barang-by-supplier/{supplierId}', [PenerimaanController::class, 'getBarangBySupplier'])->name('getBarangBySupplier');
        Route::get('/get-barang-datatable', [PenerimaanController::class, 'getBarangDatatable'])->name('getBarangDatatable');
    });
    Route::get('/detail-penerimaan', [PenerimaanController::class, 'detailIndex'])->name('detail.penerimaan.index');

    // ===================================================
    // PENJUALAN (POS)
    // ===================================================
    Route::prefix('penjualan')->name('penjualan.')->group(function () {
        Route::get('/', [PenjualanController::class, 'index'])->name('index');
        Route::post('/', [PenjualanController::class, 'store'])->name('store');
        Route::get('/{penjualan}', [PenjualanController::class, 'show'])->name('show');
        Route::delete('/{penjualan}', [PenjualanController::class, 'destroy'])->name('destroy');

        Route::prefix('cart')->name('cart.')->group(function () {
            Route::get('/', [PenjualanController::class, 'getCart'])->name('get');
            Route::post('/', [PenjualanController::class, 'addToCart'])->name('add');
            Route::put('/{cart}', [PenjualanController::class, 'updateCart'])->name('update');
            Route::delete('/{cart}', [PenjualanController::class, 'removeFromCart'])->name('destroy');
            Route::delete('/cancel', [PenjualanController::class, 'clearCart'])->name('clear');
        });
    });

    // ===================================================
    // PROCUREMENT - CART NEEDLIST
    // ===================================================
    Route::prefix('procurement/cart')->group(function () {
        Route::get('/', [CartNeedlistController::class, 'index'])->name('cart.index');
        Route::post('/store', [CartNeedlistController::class, 'store'])->name('cart.store');
        Route::delete('/{id}', [CartNeedlistController::class, 'destroy'])->name('cart.destroy');
        Route::get('/ajax/barang-variasi', [CartNeedlistController::class, 'ajaxBarangVariasi'])->name('ajax.barangVariasi');
    });

    // ===================================================
    // PROCUREMENT - NEEDLIST
    // ===================================================
    Route::prefix('needlist')->name('needlist.')->group(function () {
        Route::get('/', [NeedlistController::class, 'index'])->name('index');
        Route::get('/json', [NeedlistController::class, 'indexJson'])->name('indexJson');
        Route::get('/submitted', [NeedlistController::class, 'submitted'])->name('submitted');
        Route::get('/{id}/show', [NeedlistController::class, 'show'])->name('show');
        Route::get('/supervisor', [NeedlistController::class, 'supervisorIndex'])->name('supervisorIndex');
        Route::get('/supervisor/json', [NeedlistController::class, 'supervisorJson'])->name('supervisorJson');
        Route::get('/{id}/review', [NeedlistController::class, 'loadReview'])->name('review');
        Route::post('/{id}/submit-review', [NeedlistController::class, 'submitReview'])->name('submitReview');

        // sementara, tidak langsung update DB
        Route::post('/{id}/approve-temp', [NeedlistController::class, 'approveTemp'])->name('approveTemp');
        Route::post('/{id}/reject-temp', [NeedlistController::class, 'rejectTemp'])->name('rejectTemp');
        Route::get('/{id}/edit', [NeedlistController::class, 'edit'])->name('edit');
        Route::put('/{id}/update', [NeedlistController::class, 'update'])->name('update');
        Route::post('/store-from-cart', [NeedlistController::class, 'storeFromCart'])->name('storeFromCart');
        Route::post('/{id}/submit', [NeedlistController::class, 'submit'])->name('submit');
        Route::get('/{id}/detail-json', [NeedlistController::class, 'detailJson'])->name('detailJson');
        Route::post('/detail/store', [NeedlistController::class, 'storeDetail'])->name('detail.store');
        Route::delete('/detail/destroy', [NeedlistController::class, 'destroyDetail'])->name('detail.destroy');
        Route::post('/{id}/draft/add', [NeedlistController::class, 'addDraftDetail'])->name('draft.add');
        Route::delete('/{id}/draft/remove/{temp_id}', [NeedlistController::class, 'removeDraftDetail'])->name('draft.remove');
        Route::get('/{id}/draft/json', [NeedlistController::class, 'getDraftItemsJson'])->name('draft.json');
        Route::post('/item/{itemId}/toggle-reference', [NeedlistController::class, 'toggleReference'])->name('item.toggleReference');
        
    });

    // ===================================================
    // PROCUREMENT - SUPPLIER INQUIRY
    // ===================================================
    Route::post(
        'supplier-inquiry/{id}/store-all-from-needlist',
        [SupplierInquiryController::class, 'storeAllFromNeedlist']
    )->name('supplier-inquiry.storeAllFromNeedlist');

    Route::get('/inquiry/{id}/pdf', [SupplierInquiryController::class, 'generatePdf'])
    ->name('inquiry.pdf');
    Route::get('/inquiry/{id}/fill-modal', [SupplierInquiryController::class,'fillModal']);
    Route::post('/inquiry/{id}/store-response', [SupplierInquiryController::class,'storeResponse']);

    Route::get('/inquiry/{id}/preview-modal', [SupplierInquiryController::class,'previewModal']);

    Route::post('/needlist/{needlist}/save/selection',[SupplierConfirmationController::class, 'saveSelection'])->name('supplier.selection.save');
    Route::post('/needlist/{needlist}/create/po',[PurchaseOrderController::class, 'createFromNeedlist'])->name('supplier.create.po');

    // ===================================================
    // PROCUREMENT - SAW (Supplier Selection DSS)
    // ===================================================
    Route::prefix('procurement/supplier-selection')->name('saw.')->group(function () {
        Route::get('/detail-saw/{id}',          [SupplierSelectionSawController::class, 'detailSaw'])->name('detail');
        Route::get('/laporan',                  [SupplierSelectionSawController::class, 'laporan'])->name('laporan');
    });

    // ===================================================
    // PROCUREMENT - SAW KRITERIA & BOBOT (CRUD)
    // ===================================================
    Route::prefix('procurement/saw-kriteria')->name('saw.kriteria.')->group(function () {
        Route::get('/',               [SawKriteriaController::class, 'index'])->name('index');
        Route::get('/create',         [SawKriteriaController::class, 'create'])->name('create');
        Route::post('/',              [SawKriteriaController::class, 'store'])->name('store');
        Route::post('/normalize',     [SawKriteriaController::class, 'normalize'])->name('normalize');
        Route::get('/{kriteria}/edit',[SawKriteriaController::class, 'edit'])->name('edit');
        Route::put('/{kriteria}',     [SawKriteriaController::class, 'update'])->name('update');
        Route::delete('/{kriteria}',  [SawKriteriaController::class, 'destroy'])->name('destroy');
    });

    // ===================================================
    // PROCUREMENT - PEMILIHAN SUPPLIER (modul tersendiri)
    // ===================================================
    Route::prefix('procurement/pemilihan-supplier')->name('pemilihan-supplier.')->group(function () {
        Route::get('/',                          [SupplierRecommendationController::class, 'index'])->name('index');
        Route::get('/{needlist}/ringkasan',      [SupplierRecommendationController::class, 'ringkasan'])->name('ringkasan');
        Route::get('/{needlist}',                [SupplierRecommendationController::class, 'show'])->name('show');
        Route::post('/{needlist}/rekomendasi-semua', [SupplierSelectionSawController::class, 'hitungSemua'])->name('rekomendasi-semua');
    });

    // ===================================================
    // PROCUREMENT - SAW DATA HISTORIS (CRUD)
    // ===================================================
    Route::prefix('procurement/saw-historis')->name('saw.historis.')->group(function () {
        Route::get('/',                          [SawHistorisController::class, 'index'])->name('index');
        Route::get('/create',                    [SawHistorisController::class, 'create'])->name('create');
        Route::post('/',                         [SawHistorisController::class, 'store'])->name('store');
        Route::get('/{historis}/edit',           [SawHistorisController::class, 'edit'])->name('edit');
        Route::put('/{historis}',                [SawHistorisController::class, 'update'])->name('update');
        Route::delete('/{historis}',             [SawHistorisController::class, 'destroy'])->name('destroy');
    });

    Route::get('/purchase-order/{id}', [PurchaseOrderController::class, 'show'])
    ->name('purchase-order.show');

    Route::get('/purchase-order/{id}/print', [PurchaseOrderController::class, 'print'])
        ->name('purchase-order.print');

    // 1. Index penerimaan (list PO)
    Route::get('/receipts', [ReceiptController::class, 'index'])
        ->name('receipts.index');

    // 2. Form terima barang dari 1 PO
    Route::get('/receipts/{po}/create', [ReceiptController::class, 'create'])
        ->name('receipts.create');

    // 3. Simpan penerimaan
    Route::post('/receipts/{po}', [ReceiptController::class, 'store'])
        ->name('receipts.store');

    // 4. Detail / bukti terima barang (GRN)
    Route::get('/receipts/detail/{receipt}', [ReceiptController::class, 'show'])
        ->name('receipts.show');

    // 5. Tutup PO manual (selesaikan meski belum terpenuhi penuh)
    Route::post('/receipts/{po}/tutup', [ReceiptController::class, 'tutup'])
        ->name('receipts.tutup');


});
