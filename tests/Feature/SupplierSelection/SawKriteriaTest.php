<?php

use App\Models\SawKriteria;
use App\Models\SawNilaiHistoris;
use App\Models\SawNilaiHistorisDetail;
use App\Models\Supplier;
use App\Models\User;

beforeEach(function () {
    $this->supervisor = User::factory()->role('supervisor')->create();
});

it('lists saw kriteria for any authenticated user', function () {
    SawKriteria::factory()->create(['kode' => 'C1']);

    $response = $this->actingAs(User::factory()->role('procurement')->create())
        ->get(route('saw.kriteria.index'));

    $response->assertStatus(200);
});

it('allows supervisor to create a kriteria and auto-generates the next kode', function () {
    SawKriteria::factory()->create(['kode' => 'C1', 'bobot' => 0.3]);
    SawKriteria::factory()->create(['kode' => 'C2', 'bobot' => 0.2]);

    $response = $this->actingAs($this->supervisor)->post(route('saw.kriteria.store'), [
        'nama' => 'Kriteria Baru',
        'jenis' => 'benefit',
        'bobot' => 0.1,
        'satuan' => '%',
        'urutan' => 3,
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('saw.kriteria.index'));
    $this->assertDatabaseHas('saw_kriteria', [
        'kode' => 'C3',
        'nama' => 'Kriteria Baru',
    ]);
});

it('denies procurement role from creating or storing kriteria', function () {
    $user = User::factory()->role('procurement')->create();

    $this->actingAs($user)->get(route('saw.kriteria.create'))->assertForbidden();

    $this->actingAs($user)->post(route('saw.kriteria.store'), [
        'nama' => 'Coba',
        'jenis' => 'cost',
        'bobot' => 0.1,
        'urutan' => 0,
        'is_active' => '1',
    ])->assertForbidden();
});

it('rejects storing a kriteria whose weight would push active total above 100 percent', function () {
    SawKriteria::factory()->create(['kode' => 'C1', 'bobot' => 0.9, 'is_active' => 1]);

    $response = $this->actingAs($this->supervisor)->post(route('saw.kriteria.store'), [
        'nama' => 'Kriteria Kedua',
        'jenis' => 'benefit',
        'bobot' => 0.2,
        'urutan' => 1,
        'is_active' => '1',
    ]);

    $response->assertSessionHasErrors('bobot');
    $this->assertDatabaseMissing('saw_kriteria', ['nama' => 'Kriteria Kedua']);
});

it('allows supervisor to update a kriteria', function () {
    $kriteria = SawKriteria::factory()->create(['kode' => 'C1', 'bobot' => 0.5, 'nama' => 'Lama']);

    $response = $this->actingAs($this->supervisor)->put(route('saw.kriteria.update', $kriteria->id), [
        'nama' => 'Diperbarui',
        'jenis' => $kriteria->jenis,
        'bobot' => 0.5,
        'urutan' => 0,
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('saw.kriteria.index'));
    expect($kriteria->fresh()->nama)->toBe('Diperbarui');
});

it('allows supervisor to delete a custom kriteria that has no historis data yet', function () {
    $kriteria = SawKriteria::factory()->create(['kode' => 'C9']);

    $this->actingAs($this->supervisor)->delete(route('saw.kriteria.destroy', $kriteria->id))
        ->assertRedirect(route('saw.kriteria.index'));

    $this->assertDatabaseMissing('saw_kriteria', ['id' => $kriteria->id]);
});

it('blocks deleting a core C1-C6 kriteria even when it has no data', function () {
    $kriteria = SawKriteria::factory()->create(['kode' => 'C1']);

    $this->actingAs($this->supervisor)->delete(route('saw.kriteria.destroy', $kriteria->id))
        ->assertRedirect(route('saw.kriteria.index'));

    $this->assertDatabaseHas('saw_kriteria', ['id' => $kriteria->id]);
});

it('blocks deleting a custom kriteria that already has historis data filled in', function () {
    $kriteria = SawKriteria::factory()->create(['kode' => 'C9']);
    $historis = SawNilaiHistoris::factory()->create(['supplier_id' => Supplier::factory()->create()->id_supplier]);
    SawNilaiHistorisDetail::create(['historis_id' => $historis->id, 'kriteria_id' => $kriteria->id, 'nilai' => 4]);

    $this->actingAs($this->supervisor)->delete(route('saw.kriteria.destroy', $kriteria->id))
        ->assertRedirect(route('saw.kriteria.index'));

    $this->assertDatabaseHas('saw_kriteria', ['id' => $kriteria->id]);
});
