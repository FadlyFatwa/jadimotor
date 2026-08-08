<?php

use App\Models\Kategori;
use App\Models\MBarang;
use App\Models\User;

test('guest is redirected to login for all m_barang routes', function () {
    $barang = MBarang::factory()->create();

    $this->get(route('m_barang.index'))->assertRedirect(route('login'));
    $this->get(route('m_barang.create'))->assertRedirect(route('login'));
    $this->post(route('m_barang.store'))->assertRedirect(route('login'));
    $this->get(route('m_barang.edit', $barang->id_barang))->assertRedirect(route('login'));
    $this->put(route('m_barang.update', $barang->id_barang))->assertRedirect(route('login'));
    $this->delete(route('m_barang.destroy', $barang->id_barang))->assertRedirect(route('login'));
});

test('authenticated user can view m_barang index, create and edit pages', function () {
    $user = User::factory()->create();
    $barang = MBarang::factory()->create();

    $this->actingAs($user)->get(route('m_barang.index'))->assertOk();
    $this->actingAs($user)->get(route('m_barang.create'))->assertOk();
    $this->actingAs($user)->get(route('m_barang.edit', $barang->id_barang))->assertOk();
});

test('storing a m_barang with valid data creates a row and redirects', function () {
    $user = User::factory()->create();
    $kategori = Kategori::factory()->create();

    $response = $this->actingAs($user)->post(route('m_barang.store'), [
        'kode_barang' => 'BRG001',
        'nama_barang' => 'Kampas Rem',
        'id_kategori' => $kategori->id_kategori,
        'description' => 'Deskripsi barang',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('m_barang.index'));
    $this->assertDatabaseHas('m_barangs', [
        'kode_barang' => 'BRG001',
        'nama_barang' => 'Kampas Rem',
        'id_kategori' => $kategori->id_kategori,
    ]);
});

test('storing a m_barang as JSON request returns 201 with the created payload', function () {
    $user = User::factory()->create();
    $kategori = Kategori::factory()->create();

    $response = $this->actingAs($user)->postJson(route('m_barang.store'), [
        'kode_barang' => 'BRG002',
        'nama_barang' => 'Filter Oli',
        'id_kategori' => $kategori->id_kategori,
    ]);

    $response->assertStatus(201)->assertJsonStructure(['id_barang', 'nama_barang']);
});

test('storing a m_barang with missing required fields fails validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('m_barang.store'), [
        'kode_barang' => '',
        'nama_barang' => '',
        'id_kategori' => '',
    ]);

    $response->assertSessionHasErrors(['kode_barang', 'nama_barang', 'id_kategori']);
    $this->assertDatabaseCount('m_barangs', 0);
});

test('storing a m_barang with non-existent id_kategori fails validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('m_barang.store'), [
        'kode_barang' => 'BRG003',
        'nama_barang' => 'Barang X',
        'id_kategori' => 999999,
    ]);

    $response->assertSessionHasErrors('id_kategori');
});

test('updating a m_barang persists changes', function () {
    $user = User::factory()->create();
    $barang = MBarang::factory()->create();
    $kategori = Kategori::factory()->create();

    $response = $this->actingAs($user)->put(route('m_barang.update', $barang->id_barang), [
        'kode_barang' => $barang->kode_barang,
        'nama_barang' => 'Nama Baru',
        'id_kategori' => $kategori->id_kategori,
        'is_active' => false,
    ]);

    $response->assertRedirect(route('m_barang.index'));
    $this->assertDatabaseHas('m_barangs', [
        'id_barang' => $barang->id_barang,
        'nama_barang' => 'Nama Baru',
        'is_active' => false,
    ]);
});

test('destroying a m_barang removes the row and cascades to its variasis', function () {
    $user = User::factory()->create();
    $barang = MBarang::factory()->create();
    $variasi = \App\Models\Variasi::factory()->create(['id_barang' => $barang->id_barang]);

    $response = $this->actingAs($user)->delete(route('m_barang.destroy', $barang->id_barang));

    $response->assertRedirect(route('m_barang.index'));
    $this->assertDatabaseMissing('m_barangs', ['id_barang' => $barang->id_barang]);
    $this->assertDatabaseMissing('variasis', ['id_variasi' => $variasi->id_variasi]);
});

// --- Known application bugs, documented but not fixed (see report) ---
// MasterBarangController does not implement createMultiple(), storeMultiple() or
// cariByBarcode(), even though routes m_barang.createMultiple, m_barang.storeMultiple
// and m_barang.cariByBarcode are registered against it in routes/web.php. Hitting
// these routes currently throws a BadMethodCallException (rendered as HTTP 500).
test('m_barang.createMultiple route is broken: controller has no createMultiple method', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('m_barang.createMultiple'));

    $response->assertStatus(500);
});

test('m_barang.storeMultiple route is broken: controller has no storeMultiple method', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('m_barang.storeMultiple'));

    $response->assertStatus(500);
});

test('m_barang.cariByBarcode route is broken: controller has no cariByBarcode method', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('m_barang.cariByBarcode', ['barcode' => '12345']));

    $response->assertStatus(500);
});
