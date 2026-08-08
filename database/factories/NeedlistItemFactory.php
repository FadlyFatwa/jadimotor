<?php

namespace Database\Factories;

use App\Models\Needlist;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class NeedlistItemFactory extends Factory
{
    protected $model = \App\Models\NeedlistItem::class;

    public function definition(): array
    {
        return [
            'needlist_id' => Needlist::factory(),
            'id_variasi' => Variasi::factory(),
            'qty' => fake()->numberBetween(1, 20),
            'status' => 'pending',
            'rejected_reason' => null,
            'keterangan' => null,
            'is_reference' => false,
        ];
    }
}
