<?php

use App\Models\Unit;
use App\Models\User;

test('guest is redirected to login for all unit routes', function () {
    $unit = Unit::factory()->create();

    $this->get(route('unit.index'))->assertRedirect(route('login'));
    $this->get(route('unit.create'))->assertRedirect(route('login'));
    $this->post(route('unit.store'))->assertRedirect(route('login'));
    $this->get(route('unit.edit', $unit))->assertRedirect(route('login'));
    $this->put(route('unit.update', $unit))->assertRedirect(route('login'));
    $this->delete(route('unit.destroy', $unit))->assertRedirect(route('login'));
});

test('authenticated user can view unit index, create and edit pages', function () {
    $user = User::factory()->create();
    $unit = Unit::factory()->create();

    $this->actingAs($user)->get(route('unit.index'))->assertOk();
    $this->actingAs($user)->get(route('unit.create'))->assertOk();
    $this->actingAs($user)->get(route('unit.edit', $unit))->assertOk();
});

test('storing a unit with valid data creates a row and redirects', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('unit.store'), [
        'kode_unit' => 'PCS',
        'nama_unit' => 'Pcs',
    ]);

    $response->assertRedirect(route('unit.index'));
    $this->assertDatabaseHas('units', [
        'kode_unit' => 'PCS',
        'nama_unit' => 'Pcs',
    ]);
});

test('storing a unit with missing required fields fails validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('unit.store'), [
        'kode_unit' => '',
        'nama_unit' => '',
    ]);

    $response->assertSessionHasErrors(['kode_unit', 'nama_unit']);
    $this->assertDatabaseCount('units', 0);
});

test('storing a unit with duplicate kode_unit fails validation', function () {
    $user = User::factory()->create();
    Unit::factory()->create(['kode_unit' => 'DUP1']);

    $response = $this->actingAs($user)->post(route('unit.store'), [
        'kode_unit' => 'DUP1',
        'nama_unit' => 'Set',
    ]);

    $response->assertSessionHasErrors('kode_unit');
});

test('updating a unit persists changes', function () {
    $user = User::factory()->create();
    $unit = Unit::factory()->create();

    $response = $this->actingAs($user)->put(route('unit.update', $unit), [
        'kode_unit' => $unit->kode_unit,
        'nama_unit' => 'Lusin',
    ]);

    $response->assertRedirect(route('unit.index'));
    $this->assertDatabaseHas('units', [
        'id_unit' => $unit->id_unit,
        'nama_unit' => 'Lusin',
    ]);
});

test('destroying a unit removes the row', function () {
    $user = User::factory()->create();
    $unit = Unit::factory()->create();

    $response = $this->actingAs($user)->delete(route('unit.destroy', $unit));

    $response->assertRedirect(route('unit.index'));
    $this->assertDatabaseMissing('units', ['id_unit' => $unit->id_unit]);
});
