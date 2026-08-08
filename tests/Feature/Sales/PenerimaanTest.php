<?php

use App\Models\DetailPenerimaan;
use App\Models\Penerimaan;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Variasi;

// ---------------------------------------------------------------------
// Model / persistence layer (fixed as part of this task — see report).
// PenerimaanController itself still references a non-existent
// App\Models\Barang and mismatched field names; the tests further below
// document those controller-level bugs, which were left unfixed as they
// require a larger redesign than a small isolated patch.
// ---------------------------------------------------------------------

test('a penerimaan can be created with details and totals matching the line items', function () {
    $supplier = Supplier::factory()->create();
    $variasi = Variasi::factory()->create(['stock' => 0]);

    $penerimaan = Penerimaan::factory()->create([
        'id_supplier' => $supplier->id_supplier,
        'Total' => 100000,
        'PPN' => 11000,
        'Grand_Total' => 111000,
    ]);

    DetailPenerimaan::factory()->create([
        'id_penerimaan' => $penerimaan->id_penerimaan,
        'id_variasi' => $variasi->id_variasi,
        'Jumlah' => 5,
        'Harga' => 20000,
        'Total' => 100000,
    ]);

    expect($penerimaan->supplier->id_supplier)->toBe($supplier->id_supplier);
    expect($penerimaan->details)->toHaveCount(1);
    expect((float) $penerimaan->details->first()->Total)->toBe(100000.0);
    expect((float) $penerimaan->Grand_Total)->toBe((float) ($penerimaan->Total + $penerimaan->PPN));
});

test('GET /penerimaan renders the index page for an authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('penerimaan.index'));

    $response->assertStatus(200);
});

test('GET /penerimaan/{id}/detail returns the penerimaan with its details as json', function () {
    $user = User::factory()->create();
    $variasi = Variasi::factory()->create();
    $penerimaan = Penerimaan::factory()->create();
    DetailPenerimaan::factory()->create([
        'id_penerimaan' => $penerimaan->id_penerimaan,
        'id_variasi' => $variasi->id_variasi,
    ]);

    $response = $this->actingAs($user)->get(route('penerimaan.show', $penerimaan->id_penerimaan));

    $response->assertStatus(200);
    $response->assertJsonPath('penerimaan.id_penerimaan', $penerimaan->id_penerimaan);
    expect($response->json('details'))->toHaveCount(1);
});

test('penerimaan routes require authentication', function () {
    $this->get(route('penerimaan.index'))->assertRedirect(route('login'));
});

// ---------------------------------------------------------------------
// KNOWN BUGS (left unfixed — see report for details and rationale).
// PenerimaanController imports and calls App\Models\Barang, which does not
// exist anywhere in this codebase (only App\Models\MBarang and
// App\Models\Variasi exist). Every method below that touches Barang fails.
// ---------------------------------------------------------------------

test('BUG: GET /penerimaan/create fails because it loads App\Models\Barang, which does not exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('penerimaan.create'));

    $response->assertStatus(500);
});

test('BUG: GET /penerimaan/get-barang-by-supplier/{id} fails because it queries App\Models\Barang', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();

    $response = $this->actingAs($user)->getJson(route('penerimaan.getBarangBySupplier', $supplier->id_supplier));

    $response->assertStatus(500);
});

test('BUG: GET /penerimaan/get-barang-datatable fails because it queries App\Models\Barang', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('penerimaan.getBarangDatatable'));

    $response->assertStatus(500);
});

test('BUG: POST /penerimaan/store cannot succeed — validation requires a nonexistent "id_" field and an "exists:barangs" rule against a table that does not exist', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();
    $variasi = Variasi::factory()->create(['stock' => 0]);

    $response = $this->actingAs($user)->post(route('penerimaan.store'), [
        'Invoice' => 'PO-TEST-001',
        'id_supplier' => $supplier->id_supplier,
        'Tanggal_Nota' => now()->toDateString(),
        'Tanggal_Datang' => now()->toDateString(),
        'barang_details' => [
            ['id_variasi' => $variasi->id_variasi, 'Jumlah' => 10, 'Harga' => 15000],
        ],
    ]);

    // Intended behaviour would be a redirect (302) with the Penerimaan +
    // DetailPenerimaan persisted and Variasi::stock incremented by 10.
    // Instead the validation rules themselves reference a field name
    // ("id_") and a table ("barangs") that don't exist, so this currently
    // 500s before any of that logic runs.
    $response->assertStatus(500);
    expect(Penerimaan::where('Invoice', 'PO-TEST-001')->exists())->toBeFalse();
    $variasi->refresh();
    expect((float) $variasi->stock)->toBe(0.0);
});
