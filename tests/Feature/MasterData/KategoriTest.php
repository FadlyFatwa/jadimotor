<?php

use App\Models\Kategori;
use App\Models\MBarang;
use App\Models\User;

test('guest is redirected to login for all kategori routes', function () {
    $kategori = Kategori::factory()->create();

    $this->get(route('kategori.index'))->assertRedirect(route('login'));
    $this->get(route('kategori.create'))->assertRedirect(route('login'));
    $this->post(route('kategori.store'))->assertRedirect(route('login'));
    $this->get(route('kategori.edit', $kategori))->assertRedirect(route('login'));
    $this->put(route('kategori.update', $kategori))->assertRedirect(route('login'));
    $this->delete(route('kategori.destroy', $kategori))->assertRedirect(route('login'));
});

test('authenticated user can view kategori index and create pages', function () {
    $user = User::factory()->create();
    Kategori::factory()->count(3)->create();

    $this->actingAs($user)->get(route('kategori.index'))->assertOk();
    $this->actingAs($user)->get(route('kategori.create'))->assertOk();
});

test('authenticated user can view kategori edit page', function () {
    $user = User::factory()->create();
    $kategori = Kategori::factory()->create();

    $this->actingAs($user)->get(route('kategori.edit', $kategori))->assertOk();
});

test('storing a kategori with valid data creates a row and redirects', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('kategori.store'), [
        'kode_kategori' => 'CAT01',
        'nama_kategori' => 'Oli Mesin',
        'description' => 'Kategori oli mesin',
    ]);

    $response->assertRedirect(route('kategori.index'));
    $this->assertDatabaseHas('kategoris', [
        'kode_kategori' => 'CAT01',
        'nama_kategori' => 'Oli Mesin',
    ]);
});

test('slug is auto-generated from nama_kategori when not supplied', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('kategori.store'), [
        'kode_kategori' => 'CAT02',
        'nama_kategori' => 'Ban Motor',
    ]);

    $this->assertDatabaseHas('kategoris', [
        'kode_kategori' => 'CAT02',
        'slug' => 'ban-motor',
    ]);
});

test('storing a kategori with missing required fields fails validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('kategori.store'), [
        'kode_kategori' => '',
        'nama_kategori' => '',
    ]);

    $response->assertSessionHasErrors(['kode_kategori', 'nama_kategori']);
    $this->assertDatabaseCount('kategoris', 0);
});

test('storing a kategori with duplicate kode_kategori fails validation', function () {
    $user = User::factory()->create();
    Kategori::factory()->create(['kode_kategori' => 'DUP01']);

    $response = $this->actingAs($user)->post(route('kategori.store'), [
        'kode_kategori' => 'DUP01',
        'nama_kategori' => 'Kategori Lain',
    ]);

    $response->assertSessionHasErrors('kode_kategori');
});

test('updating a kategori persists changes', function () {
    $user = User::factory()->create();
    $kategori = Kategori::factory()->create();

    $response = $this->actingAs($user)->put(route('kategori.update', $kategori), [
        'kode_kategori' => $kategori->kode_kategori,
        'nama_kategori' => 'Nama Baru',
        'description' => 'Updated',
    ]);

    $response->assertRedirect(route('kategori.index'));
    $this->assertDatabaseHas('kategoris', [
        'id_kategori' => $kategori->id_kategori,
        'nama_kategori' => 'Nama Baru',
    ]);
});

test('destroying a kategori removes the row and cascades to child m_barangs', function () {
    $user = User::factory()->create();
    $kategori = Kategori::factory()->create();
    $barang = MBarang::factory()->create(['id_kategori' => $kategori->id_kategori]);

    $response = $this->actingAs($user)->delete(route('kategori.destroy', $kategori));

    $response->assertRedirect(route('kategori.index'));
    $this->assertDatabaseMissing('kategoris', ['id_kategori' => $kategori->id_kategori]);
    $this->assertDatabaseMissing('m_barangs', ['id_barang' => $barang->id_barang]);
});
