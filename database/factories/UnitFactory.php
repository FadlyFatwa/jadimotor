<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UnitFactory extends Factory
{
    protected $model = \App\Models\Unit::class;

    public function definition(): array
    {
        return [
            'kode_unit' => strtoupper(Str::random(4)),
            'nama_unit' => fake()->randomElement(['Pcs', 'Box', 'Set', 'Unit', 'Lusin']),
        ];
    }
}
