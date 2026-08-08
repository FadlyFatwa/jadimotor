<?php

namespace Database\Factories;

use App\Models\MBarang;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariasiFactory extends Factory
{
    protected $model = \App\Models\Variasi::class;

    public function definition(): array
    {
        return [
            'barcode' => fake()->unique()->numerify('#############'),
            'nama_variasi' => ucfirst(fake()->words(3, true)),
            'part_number' => strtoupper(fake()->bothify('PN-####??')),
            'id_barang' => MBarang::factory(),
            'id_unit' => Unit::factory(),
            'harga_jual' => fake()->randomFloat(2, 10000, 500000),
            'stock' => fake()->randomFloat(2, 0, 100),
            'status' => 'active',
            'is_active' => true,
            'tier' => null,
        ];
    }
}
