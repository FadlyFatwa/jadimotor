<?php

namespace Database\Factories;

use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemCategorizationLogFactory extends Factory
{
    protected $model = \App\Models\ItemCategorizationLog::class;

    public function definition(): array
    {
        return [
            'id_variasi' => Variasi::factory(),
            'barcode' => fake()->unique()->numerify('#############'),
            'nama_variasi_lama' => ucfirst(fake()->words(3, true)),
            'nama_variasi_baru' => ucfirst(fake()->words(2, true)),
            'id_barang_baru' => null,
            'part_number_baru' => null,
            'dikategorikan_oleh' => null,
            'dikategorikan_at' => now(),
        ];
    }
}
