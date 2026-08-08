<?php

namespace Database\Factories;

use App\Models\Penjualan;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenjualanDetailFactory extends Factory
{
    protected $model = \App\Models\PenjualanDetail::class;

    public function definition(): array
    {
        $harga = fake()->randomFloat(2, 10000, 500000);
        $qty = fake()->numberBetween(1, 5);

        return [
            'id_penjualan' => Penjualan::factory(),
            'id_variasi' => Variasi::factory(),
            'nama_barang_jual' => ucfirst(fake()->words(3, true)),
            'harga' => $harga,
            'qty' => $qty,
            'subtotal' => $harga * $qty,
        ];
    }
}
