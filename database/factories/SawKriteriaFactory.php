<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SawKriteriaFactory extends Factory
{
    protected $model = \App\Models\SawKriteria::class;

    public function definition(): array
    {
        return [
            'kode' => 'C' . fake()->unique()->numberBetween(1, 999),
            'nama' => ucfirst(fake()->words(2, true)),
            'jenis' => fake()->randomElement(['cost', 'benefit']),
            'bobot' => 0.2000,
            'satuan' => null,
            'is_active' => true,
            'urutan' => 0,
        ];
    }
}
