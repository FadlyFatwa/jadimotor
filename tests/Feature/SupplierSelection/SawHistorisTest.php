<?php

use App\Models\SawKriteria;
use App\Models\SawNilaiHistoris;
use App\Models\SawNilaiHistorisDetail;
use App\Models\Supplier;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->role('admin')->create();
    $this->procurement = User::factory()->role('procurement')->create();
});

it('lists historis data for any authenticated user including procurement', function () {
    $historis = SawNilaiHistoris::factory()->create();

    $this->actingAs($this->procurement)
        ->get(route('saw.historis.index'))
        ->assertStatus(200);
});

it('denies procurement role from creating, updating, deleting or viewing the create/edit form', function () {
    $historis = SawNilaiHistoris::factory()->create();

    $this->actingAs($this->procurement)->get(route('saw.historis.create'))->assertForbidden();
    $this->actingAs($this->procurement)->get(route('saw.historis.edit', $historis->id))->assertForbidden();

    $this->actingAs($this->procurement)->post(route('saw.historis.store'), [
        'supplier_id' => $historis->supplier_id,
        'periode_mulai' => '2026-01-01',
        'periode_akhir' => '2026-03-01',
    ])->assertForbidden();

    $this->actingAs($this->procurement)->put(route('saw.historis.update', $historis->id), [
        'supplier_id' => $historis->supplier_id,
        'periode_mulai' => '2026-01-01',
        'periode_akhir' => '2026-03-01',
    ])->assertForbidden();

    $this->actingAs($this->procurement)->delete(route('saw.historis.destroy', $historis->id))
        ->assertForbidden();
});

it('allows admin to store historis data with dynamic per-kriteria values (C2-C6)', function () {
    $supplier = Supplier::factory()->create();
    $kriteria = collect(['C2', 'C3', 'C4', 'C5', 'C6'])
        ->mapWithKeys(fn ($kode) => [$kode => SawKriteria::factory()->create(['kode' => $kode, 'is_active' => 1])]);

    $response = $this->actingAs($this->admin)->post(route('saw.historis.store'), [
        'supplier_id' => $supplier->id_supplier,
        'periode_mulai' => '2026-01-01',
        'periode_akhir' => '2026-06-01',
        'jumlah_transaksi' => 12,
        'nilai_kriteria' => [
            $kriteria['C2']->id => 3, // C2 is a 1-5 payment-term band, not raw days (see form.blade.php)
            $kriteria['C3']->id => '5,5',
            $kriteria['C4']->id => '95,25',
            $kriteria['C5']->id => '98',
            $kriteria['C6']->id => 4,
        ],
    ]);

    $response->assertRedirect(route('saw.historis.index'));

    $record = SawNilaiHistoris::where('supplier_id', $supplier->id_supplier)->firstOrFail();

    expect((float) $record->details->firstWhere('kriteria_id', $kriteria['C3']->id)->nilai)->toEqual(5.5);
    expect((float) $record->details->firstWhere('kriteria_id', $kriteria['C4']->id)->nilai)->toEqual(95.25);
    expect((int) $record->jumlah_transaksi_manual)->toBe(12);
});

it('redirects to edit instead of creating a duplicate when supplier already has a historis record', function () {
    $existing = SawNilaiHistoris::factory()->create();

    $response = $this->actingAs($this->admin)->post(route('saw.historis.store'), [
        'supplier_id' => $existing->supplier_id,
        'periode_mulai' => '2026-01-01',
        'periode_akhir' => '2026-06-01',
    ]);

    $response->assertRedirect(route('saw.historis.edit', $existing->id));
    expect(SawNilaiHistoris::where('supplier_id', $existing->supplier_id)->count())->toBe(1);
});

it('allows admin to update historis data', function () {
    $kriteriaC6 = SawKriteria::factory()->create(['kode' => 'C6', 'is_active' => 1]);
    $historis = SawNilaiHistoris::factory()->create();
    seedHistorisNilai($historis, ['C6' => 3]);

    $response = $this->actingAs($this->admin)->put(route('saw.historis.update', $historis->id), [
        'supplier_id' => $historis->supplier_id,
        'periode_mulai' => $historis->periode_mulai->toDateString(),
        'periode_akhir' => $historis->periode_akhir->toDateString(),
        'nilai_kriteria' => [$kriteriaC6->id => 5],
    ]);

    $response->assertRedirect(route('saw.historis.index'));
    $nilai = SawNilaiHistorisDetail::where('historis_id', $historis->id)
        ->where('kriteria_id', $kriteriaC6->id)->value('nilai');
    expect((int) $nilai)->toBe(5);
});

it('allows admin to delete historis data', function () {
    $historis = SawNilaiHistoris::factory()->create();

    $this->actingAs($this->admin)->delete(route('saw.historis.destroy', $historis->id))
        ->assertRedirect(route('saw.historis.index'));

    $this->assertDatabaseMissing('saw_nilai_historis', ['id' => $historis->id]);
});

it('stores values for custom kriteria (outside C1-C6) into saw_nilai_historis_detail', function () {
    $supplier = Supplier::factory()->create();
    $kriteriaCustom = SawKriteria::factory()->create(['kode' => 'C7', 'nama' => 'Garansi Produk', 'is_active' => 1]);

    $response = $this->actingAs($this->admin)->post(route('saw.historis.store'), [
        'supplier_id' => $supplier->id_supplier,
        'periode_mulai' => '2026-01-01',
        'periode_akhir' => '2026-06-01',
        'nilai_kriteria' => [$kriteriaCustom->id => '4,5'],
    ]);

    $response->assertRedirect(route('saw.historis.index'));

    $historis = SawNilaiHistoris::where('supplier_id', $supplier->id_supplier)->firstOrFail();
    $this->assertDatabaseHas('saw_nilai_historis_detail', [
        'historis_id' => $historis->id,
        'kriteria_id' => $kriteriaCustom->id,
        'nilai' => 4.5,
    ]);
});

it('removes a custom kriteria value when the field is cleared on update', function () {
    $kriteriaCustom = SawKriteria::factory()->create(['kode' => 'C7', 'is_active' => 1]);
    $historis = SawNilaiHistoris::factory()->create();
    SawNilaiHistorisDetail::create(['historis_id' => $historis->id, 'kriteria_id' => $kriteriaCustom->id, 'nilai' => 3]);

    $this->actingAs($this->admin)->put(route('saw.historis.update', $historis->id), [
        'supplier_id' => $historis->supplier_id,
        'periode_mulai' => $historis->periode_mulai->toDateString(),
        'periode_akhir' => $historis->periode_akhir->toDateString(),
        'nilai_kriteria' => [$kriteriaCustom->id => ''],
    ])->assertRedirect(route('saw.historis.index'));

    $this->assertDatabaseMissing('saw_nilai_historis_detail', [
        'historis_id' => $historis->id,
        'kriteria_id' => $kriteriaCustom->id,
    ]);
});
