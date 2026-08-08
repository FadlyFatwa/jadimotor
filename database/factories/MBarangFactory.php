<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MBarangFactory extends Factory
{
    protected $model = \App\Models\MBarang::class;

    public function definition(): array
    {
        return [
            'kode_barang' => strtoupper(Str::random(6)),
            'nama_barang' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'is_active' => true,
            'id_kategori' => Kategori::factory(),
        ];
    }
}
