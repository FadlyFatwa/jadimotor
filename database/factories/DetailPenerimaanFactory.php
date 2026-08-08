<?php

namespace Database\Factories;

use App\Models\Penerimaan;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetailPenerimaanFactory extends Factory
{
    protected $model = \App\Models\DetailPenerimaan::class;

    public function definition(): array
    {
        $jumlah = fake()->numberBetween(1, 20);
        $harga = fake()->randomFloat(2, 10000, 500000);

        return [
            'id_penerimaan' => Penerimaan::factory(),
            'id_variasi' => Variasi::factory(),
            'Jumlah' => $jumlah,
            'Harga' => $harga,
            'Total' => $jumlah * $harga,
            'Tanggal' => fake()->date(),
            'Status' => 'disimpan',
        ];
    }
}
