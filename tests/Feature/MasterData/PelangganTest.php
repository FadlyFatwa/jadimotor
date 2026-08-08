<?php

use App\Models\Pelanggan;
use App\Models\User;

test('guest is redirected to login for all pelanggan routes', function () {
    $pelanggan = Pelanggan::factory()->create();

    $this->get(route('pelanggan.index'))->assertRedirect(route('login'));
    $this->get(route('pelanggan.create'))->assertRedirect(route('login'));
    $this->post(route('pelanggan.store'))->assertRedirect(route('login'));
    $this->get(route('pelanggan.edit', $pelanggan))->assertRedirect(route('login'));
    $this->put(route('pelanggan.update', $pelanggan))->assertRedirect(route('login'));
    $this->delete(route('pelanggan.destroy', $pelanggan))->assertRedirect(route('login'));
});

test('authenticated user can view pelanggan index, create and edit pages', function () {
    $user = User::factory()->create();
    $pelanggan = Pelanggan::factory()->create();

    $this->actingAs($user)->get(route('pelanggan.index'))->assertOk();
    $this->actingAs($user)->get(route('pelanggan.create'))->assertOk();
    $this->actingAs($user)->get(route('pelanggan.edit', $pelanggan))->assertOk();
});

test('storing a pelanggan with valid data creates a row and redirects', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('pelanggan.store'), [
        'nama' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'telepon' => '081234567890',
        'alamat' => 'Jl. Sudirman No. 10',
    ]);

    $response->assertRedirect(route('pelanggan.index'));
    $this->assertDatabaseHas('pelanggans', [
        'nama' => 'Budi Santoso',
        'email' => 'budi@example.com',
    ]);
});

test('storing a pelanggan without optional fields still succeeds since only nama is required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('pelanggan.store'), [
        'nama' => 'Tanpa Email',
    ]);

    $response->assertRedirect(route('pelanggan.index'));
    $this->assertDatabaseHas('pelanggans', [
        'nama' => 'Tanpa Email',
        'email' => null,
    ]);
});

test('storing a pelanggan with missing nama fails validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('pelanggan.store'), [
        'nama' => '',
    ]);

    $response->assertSessionHasErrors('nama');
    $this->assertDatabaseCount('pelanggans', 0);
});

test('storing a pelanggan with duplicate email fails validation', function () {
    $user = User::factory()->create();
    Pelanggan::factory()->create(['email' => 'dup@example.com']);

    $response = $this->actingAs($user)->post(route('pelanggan.store'), [
        'nama' => 'Orang Lain',
        'email' => 'dup@example.com',
    ]);

    $response->assertSessionHasErrors('email');
});

test('updating a pelanggan persists changes', function () {
    $user = User::factory()->create();
    $pelanggan = Pelanggan::factory()->create();

    $response = $this->actingAs($user)->put(route('pelanggan.update', $pelanggan), [
        'nama' => 'Nama Baru',
        'email' => $pelanggan->email,
        'telepon' => $pelanggan->telepon,
        'alamat' => $pelanggan->alamat,
    ]);

    $response->assertRedirect(route('pelanggan.index'));
    $this->assertDatabaseHas('pelanggans', [
        'id' => $pelanggan->id,
        'nama' => 'Nama Baru',
    ]);
});

test('updating a pelanggan can keep its own email without triggering uniqueness error', function () {
    $user = User::factory()->create();
    $pelanggan = Pelanggan::factory()->create(['email' => 'keep@example.com']);

    $response = $this->actingAs($user)->put(route('pelanggan.update', $pelanggan), [
        'nama' => $pelanggan->nama,
        'email' => 'keep@example.com',
        'telepon' => $pelanggan->telepon,
        'alamat' => $pelanggan->alamat,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('pelanggan.index'));
});

test('destroying a pelanggan removes the row', function () {
    $user = User::factory()->create();
    $pelanggan = Pelanggan::factory()->create();

    $response = $this->actingAs($user)->delete(route('pelanggan.destroy', $pelanggan));

    $response->assertRedirect(route('pelanggan.index'));
    $this->assertDatabaseMissing('pelanggans', ['id' => $pelanggan->id]);
});
