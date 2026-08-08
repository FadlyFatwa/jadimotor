<?php

use App\Models\MBarang;
use App\Models\Supplier;
use App\Models\SupplierVariasi;
use App\Models\Unit;
use App\Models\User;
use App\Models\Variasi;

function md_valid_supplier_data(Supplier $supplier, array $overrides = []): array
{
    return array_merge([
        'id_supplier' => $supplier->id_supplier,
        'harga_beli' => '100000',
        'harga_list' => '120000',
        'kode_list' => 'LIST01',
        'kode_beli' => 'BUY01',
        'diskon' => 0,
    ], $overrides);
}

test('guest is redirected to login for all barang (variasi) routes', function () {
    $variasi = Variasi::factory()->create();

    $this->get(route('barang.index'))->assertRedirect(route('login'));
    $this->get(route('barang.create'))->assertRedirect(route('login'));
    $this->post(route('barang.store'))->assertRedirect(route('login'));
    $this->get(route('barang.edit', $variasi->id_variasi))->assertRedirect(route('login'));
    $this->put(route('barang.update', $variasi->id_variasi))->assertRedirect(route('login'));
    $this->delete(route('barang.destroy', $variasi->id_variasi))->assertRedirect(route('login'));
    $this->get(route('barang.createMultiple'))->assertRedirect(route('login'));
    $this->post(route('barang.storeMultiple'))->assertRedirect(route('login'));
    $this->get(route('barang.cariByBarcode'))->assertRedirect(route('login'));
});

test('authenticated user can view barang index, create, edit and createMultiple pages', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create();

    $this->actingAs($user)->get(route('barang.index'))->assertOk();
    $this->actingAs($user)->get(route('barang.create'))->assertOk();
    $this->actingAs($user)->get(route('barang.edit', $variasi->id_variasi))->assertOk();
    $this->actingAs($user)->get(route('barang.createMultiple'))->assertOk();
});

test('barang index ajax request returns datatables json', function () {
    $user = User::factory()->create();
    Variasi::factory()->count(2)->create();

    $response = $this->actingAs($user)->get(route('barang.index'), ['X-Requested-With' => 'XMLHttpRequest']);

    $response->assertOk();
    $response->assertJsonStructure(['data']);
});

test('storing a variasi with valid data creates a row plus its supplier_barang link', function () {
    $user = User::factory()->create();
    $barang = MBarang::factory()->create();
    $unit = Unit::factory()->create();
    $supplier = Supplier::factory()->create();

    $response = $this->actingAs($user)->post(route('barang.store'), [
        'id_barang' => $barang->id_barang,
        'barcode' => '1234567890123',
        'nama_variasi' => 'Kampas Rem Depan',
        'id_unit' => $unit->id_unit,
        'harga_jual' => '150000',
        'supplier_data' => [md_valid_supplier_data($supplier)],
    ]);

    $response->assertRedirect(route('barang.index'));
    $this->assertDatabaseHas('variasis', [
        'barcode' => '1234567890123',
        'nama_variasi' => 'Kampas Rem Depan',
        'id_barang' => $barang->id_barang,
    ]);
    $this->assertDatabaseHas('supplier_barang', [
        'id_supplier' => $supplier->id_supplier,
        'harga_beli' => 100000,
    ]);
});

test('storing a variasi with missing required fields fails validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('barang.store'), [
        'id_barang' => '',
        'barcode' => '',
        'nama_variasi' => '',
        'id_unit' => '',
        'harga_jual' => '',
        'supplier_data' => [],
    ]);

    $response->assertSessionHasErrors(['id_barang', 'barcode', 'nama_variasi', 'id_unit', 'harga_jual', 'supplier_data']);
    $this->assertDatabaseCount('variasis', 0);
});

test('storing a variasi with duplicate barcode fails validation', function () {
    $user = User::factory()->create();
    $existing = Variasi::factory()->create(['barcode' => 'DUPBARCODE']);
    $barang = MBarang::factory()->create();
    $unit = Unit::factory()->create();
    $supplier = Supplier::factory()->create();

    $response = $this->actingAs($user)->post(route('barang.store'), [
        'id_barang' => $barang->id_barang,
        'barcode' => 'DUPBARCODE',
        'nama_variasi' => 'Variasi Lain',
        'id_unit' => $unit->id_unit,
        'harga_jual' => '100000',
        'supplier_data' => [md_valid_supplier_data($supplier)],
    ]);

    $response->assertSessionHasErrors('barcode');
});

test('storing a variasi without harga_list/kode_list/kode_beli in supplier_data now fails validation instead of crashing', function () {
    // Regression test for a bug found in VariasiController@store: the supplier_barang
    // table has NOT NULL columns harga_list, kode_list and kode_beli, but the original
    // validation only required id_supplier and harga_beli, which meant an otherwise
    // "valid" request would blow up with a SQL "column cannot be null" error instead
    // of a normal validation response. Fixed by adding the missing required rules.
    $user = User::factory()->create();
    $barang = MBarang::factory()->create();
    $unit = Unit::factory()->create();
    $supplier = Supplier::factory()->create();

    $response = $this->actingAs($user)->post(route('barang.store'), [
        'id_barang' => $barang->id_barang,
        'barcode' => 'NOEXTRAFIELDS',
        'nama_variasi' => 'Variasi Tanpa Harga List',
        'id_unit' => $unit->id_unit,
        'harga_jual' => '100000',
        'supplier_data' => [[
            'id_supplier' => $supplier->id_supplier,
            'harga_beli' => '90000',
        ]],
    ]);

    $response->assertSessionHasErrors([
        'supplier_data.0.harga_list',
        'supplier_data.0.kode_list',
        'supplier_data.0.kode_beli',
    ]);
    $this->assertDatabaseCount('variasis', 0);
});

test('updating a variasi persists changes and replaces supplier_data', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create();
    $oldSv = SupplierVariasi::factory()->create(['id_variasi' => $variasi->id_variasi]);
    $newSupplier = Supplier::factory()->create();

    $response = $this->actingAs($user)->put(route('barang.update', $variasi->id_variasi), [
        'id_barang' => $variasi->id_barang,
        'barcode' => $variasi->barcode,
        'nama_variasi' => 'Nama Variasi Baru',
        'id_unit' => $variasi->id_unit,
        'harga_jual' => '200000',
        'supplier_data' => [md_valid_supplier_data($newSupplier)],
    ]);

    $response->assertRedirect(route('barang.index'));
    $this->assertDatabaseHas('variasis', [
        'id_variasi' => $variasi->id_variasi,
        'nama_variasi' => 'Nama Variasi Baru',
    ]);
    $this->assertDatabaseMissing('supplier_barang', ['id_supplier_variasi' => $oldSv->id_supplier_variasi]);
    $this->assertDatabaseHas('supplier_barang', ['id_supplier' => $newSupplier->id_supplier]);
});

test('destroying a variasi removes the row and cascades to supplier_barang', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create();
    $sv = SupplierVariasi::factory()->create(['id_variasi' => $variasi->id_variasi]);

    $response = $this->actingAs($user)->delete(route('barang.destroy', $variasi->id_variasi));

    $response->assertRedirect(route('barang.index'));
    $this->assertDatabaseMissing('variasis', ['id_variasi' => $variasi->id_variasi]);
    $this->assertDatabaseMissing('supplier_barang', ['id_supplier_variasi' => $sv->id_supplier_variasi]);
});

test('cariByBarcode finds a variasi by barcode (fixed bug: previously referenced an undefined ProdukVariasi class)', function () {
    $user = User::factory()->create();
    $barang = MBarang::factory()->create(['nama_barang' => 'Filter Udara']);
    $variasi = Variasi::factory()->create(['id_barang' => $barang->id_barang, 'barcode' => 'FINDME123']);

    $response = $this->actingAs($user)->get(route('barang.cariByBarcode', ['barcode' => 'FINDME123']));

    $response->assertOk()->assertJson([
        'status' => 'success',
        'data' => [
            'id' => $variasi->id_variasi,
            'nama_barang' => 'Filter Udara',
        ],
    ]);
});

test('cariByBarcode returns 404 when barcode not found', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('barang.cariByBarcode', ['barcode' => 'DOES-NOT-EXIST']));

    $response->assertStatus(404)->assertJson(['status' => 'error']);
});

// --- Known application bugs, documented but not fixed (see report) ---
// VariasiController@storeMultiple references a Barang model that does not exist
// anywhere in app/Models, and validates 'barangs.*.barcode' against a `barangs`
// database table that does not exist either (the real table is `m_barangs`).
// Any call to this endpoint currently throws a QueryException / Error (HTTP 500).
test('barang.storeMultiple route is broken: references nonexistent Barang model/table', function () {
    $user = User::factory()->create();
    $kategori = \App\Models\Kategori::factory()->create();
    $supplier = Supplier::factory()->create();
    $barang = MBarang::factory()->create();

    $response = $this->actingAs($user)->post(route('barang.storeMultiple'), [
        'barangs' => [[
            'barcode' => 'MULTI001',
            'nama_barang' => 'Barang Multi',
            'id_kategori' => $kategori->id_kategori,
            'id_supplier' => $supplier->id_supplier,
            'id_barang' => $barang->id_barang,
            'harga_jual' => '10000',
        ]],
    ]);

    $response->assertStatus(500);
});

// VariasiController does not implement generateBarcode(), even though the route
// barcode.generate is registered against it in routes/web.php.
test('barcode.generate route is broken: controller has no generateBarcode method', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('barcode.generate'));

    $response->assertStatus(500);
});

test('sku index pages (variasi.index and variasi.index.terkategori) render for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('variasi.index'))->assertOk();
    $this->actingAs($user)->get(route('variasi.index.terkategori'))->assertOk();
});

test('variasi datatable json endpoint returns matching records', function () {
    $user = User::factory()->create();
    Variasi::factory()->count(3)->create();

    $response = $this->actingAs($user)->post(route('variasi.datatable'), [
        'draw' => 1,
        'start' => 0,
        'length' => 10,
    ]);

    $response->assertOk();
    $response->assertJson(['draw' => 1, 'recordsTotal' => 3, 'recordsFiltered' => 3]);
    $response->assertJsonCount(3, 'data');
});

test('variasi detail endpoint returns full structured payload', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create();
    SupplierVariasi::factory()->create(['id_variasi' => $variasi->id_variasi]);

    $response = $this->actingAs($user)->get(route('variasi.detail', $variasi->id_variasi));

    $response->assertOk();
    $response->assertJsonStructure([
        'barcode', 'master_barang', 'variasi', 'penggunaan_mobil', 'suppliers', 'harga_jual', 'stock', 'is_active',
    ]);
});

test('variasi detail returns 404 for non-existent id', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('variasi.detail', 999999))->assertStatus(404);
});
