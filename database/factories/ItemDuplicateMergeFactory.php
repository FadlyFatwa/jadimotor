<?php

namespace Database\Factories;

use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemDuplicateMergeFactory extends Factory
{
    protected $model = \App\Models\ItemDuplicateMerge::class;

    public function definition(): array
    {
        return [
            'target_id_variasi' => Variasi::factory(),
            'target_barcode' => fake()->unique()->numerify('#############'),
            'merged_id_variasi' => Variasi::factory(),
            'merged_barcode' => fake()->unique()->numerify('#############'),
            'merged_nama_variasi' => ucfirst(fake()->words(3, true)),
            'stock_moved' => fake()->randomFloat(2, 0, 50),
            'merged_by' => null,
            'merged_at' => now(),
        ];
    }
}
