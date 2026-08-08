<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenerimaanFactory extends Factory
{
    protected $model = \App\Models\Penerimaan::class;

    public function definition(): array
    {
        $total = fake()->randomFloat(2, 100000, 5000000);

        return [
            'id_supplier' => Supplier::factory(),
            'Invoice' => strtoupper('INV-'.fake()->unique()->bothify('####??')),
            'Tanggal_Nota' => fake()->date(),
            'Tanggal_Datang' => fake()->date(),
            'Jatuh_Tempo' => fake()->date(),
            'Total' => $total,
            'PPN' => 0,
            'Grand_Total' => $total,
            'status' => 'lunas',
        ];
    }
}
