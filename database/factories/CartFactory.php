<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    protected $model = \App\Models\Cart::class;

    public function definition(): array
    {
        $harga = fake()->randomFloat(2, 10000, 500000);
        $diskon = 0;
        $qty = fake()->numberBetween(1, 5);

        return [
            'user_id' => User::factory(),
            'id_variasi' => Variasi::factory(),
            'nama_barang_jual' => ucfirst(fake()->words(3, true)),
            'harga' => $harga,
            'diskon' => $diskon,
            'qty' => $qty,
            'subtotal' => ($harga - $diskon) * $qty,
        ];
    }
}
