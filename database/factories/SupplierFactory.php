<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SupplierFactory extends Factory
{
    protected $model = \App\Models\Supplier::class;

    public function definition(): array
    {
        return [
            'kode_supplier' => strtoupper(Str::random(6)),
            'nama_supplier' => fake()->company(),
            'no_telp' => fake()->numerify('08##########'),
            'alamat' => fake()->streetAddress(),
        ];
    }
}
