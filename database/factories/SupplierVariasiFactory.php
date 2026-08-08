<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierVariasiFactory extends Factory
{
    protected $model = \App\Models\SupplierVariasi::class;

    public function definition(): array
    {
        return [
            'id_variasi' => Variasi::factory(),
            'id_supplier' => Supplier::factory(),
            'harga_list' => fake()->numberBetween(10000, 500000),
            'kode_list' => strtoupper(fake()->bothify('LIST-####')),
            'harga_beli' => fake()->numberBetween(8000, 400000),
            'kode_beli' => strtoupper(fake()->bothify('BUY-####')),
            'diskon' => fake()->randomFloat(1, 0, 9.9),
        ];
    }
}
