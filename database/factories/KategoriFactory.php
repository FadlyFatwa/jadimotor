<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KategoriFactory extends Factory
{
    protected $model = \App\Models\Kategori::class;

    public function definition(): array
    {
        $nama = ucfirst(fake()->unique()->word());

        return [
            'kode_kategori' => strtoupper(Str::random(5)),
            'nama_kategori' => $nama,
            'slug' => Str::slug($nama).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->sentence(),
        ];
    }
}
