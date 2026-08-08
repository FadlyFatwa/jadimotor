<?php

use App\Models\Supplier;
use App\Models\SupplierVariasi;
use App\Models\User;

test('guest is redirected to login for all supplier routes', function () {
    $supplier = Supplier::factory()->create();

    $this->get(route('supplier.index'))->assertRedirect(route('login'));
    $this->get(route('supplier.create'))->assertRedirect(route('login'));
    $this->post(route('supplier.store'))->assertRedirect(route('login'));
    $this->get(route('supplier.edit', $supplier))->assertRedirect(route('login'));
    $this->put(route('supplier.update', $supplier))->assertRedirect(route('login'));
    $this->delete(route('supplier.destroy', $supplier))->assertRedirect(route('login'));
});

test('authenticated user can view supplier index, create and edit pages', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();

    $this->actingAs($user)->get(route('supplier.index'))->assertOk();
    $this->actingAs($user)->get(route('supplier.create'))->assertOk();
    $this->actingAs($user)->get(route('supplier.edit', $supplier))->assertOk();
});

test('storing a supplier with valid data creates a row and redirects', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('supplier.store'), [
        'kode_supplier' => 'SUP01',
        'nama_supplier' => 'PT Sumber Jaya',
        'no_telp' => '081234567890',
        'alamat' => 'Jl. Merdeka No. 1',
    ]);

    $response->assertRedirect(route('supplier.index'));
    $this->assertDatabaseHas('suppliers', [
        'kode_supplier' => 'SUP01',
        'nama_supplier' => 'PT Sumber Jaya',
    ]);
});

test('storing a supplier with missing required fields fails validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('supplier.store'), [
        'kode_supplier' => '',
        'nama_supplier' => '',
        'no_telp' => '',
        'alamat' => '',
    ]);

    $response->assertSessionHasErrors(['kode_supplier', 'nama_supplier', 'no_telp', 'alamat']);
    $this->assertDatabaseCount('suppliers', 0);
});

test('storing a supplier with duplicate kode_supplier fails validation', function () {
    $user = User::factory()->create();
    Supplier::factory()->create(['kode_supplier' => 'DUP01']);

    $response = $this->actingAs($user)->post(route('supplier.store'), [
        'kode_supplier' => 'DUP01',
        'nama_supplier' => 'Supplier Lain',
        'no_telp' => '08123',
        'alamat' => 'Alamat lain',
    ]);

    $response->assertSessionHasErrors('kode_supplier');
});

test('updating a supplier persists changes', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();

    $response = $this->actingAs($user)->put(route('supplier.update', $supplier), [
        'kode_supplier' => $supplier->kode_supplier,
        'nama_supplier' => 'Nama Supplier Baru',
        'no_telp' => $supplier->no_telp,
        'alamat' => $supplier->alamat,
    ]);

    $response->assertRedirect(route('supplier.index'));
    $this->assertDatabaseHas('suppliers', [
        'id_supplier' => $supplier->id_supplier,
        'nama_supplier' => 'Nama Supplier Baru',
    ]);
});

test('destroying a supplier removes the row and cascades to supplier_barang rows', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();
    $sv = SupplierVariasi::factory()->create(['id_supplier' => $supplier->id_supplier]);

    $response = $this->actingAs($user)->delete(route('supplier.destroy', $supplier));

    $response->assertRedirect(route('supplier.index'));
    $this->assertDatabaseMissing('suppliers', ['id_supplier' => $supplier->id_supplier]);
    $this->assertDatabaseMissing('supplier_barang', ['id_supplier_variasi' => $sv->id_supplier_variasi]);
});
