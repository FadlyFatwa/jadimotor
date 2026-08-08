<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SawNilaiHistorisFactory extends Factory
{
    protected $model = \App\Models\SawNilaiHistoris::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'periode_mulai' => now()->subMonths(3)->toDateString(),
            'periode_akhir' => now()->toDateString(),
            'jumlah_transaksi' => fake()->numberBetween(1, 50),
            'jumlah_transaksi_manual' => 0,
            'catatan' => null,
        ];
    }
}
