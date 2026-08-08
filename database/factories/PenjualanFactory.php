<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenjualanFactory extends Factory
{
    protected $model = \App\Models\Penjualan::class;

    public function definition(): array
    {
        $total = fake()->randomFloat(2, 50000, 1000000);
        $diskon = 0;

        return [
            'nomor_nota' => strtoupper('INV-'.fake()->unique()->bothify('####??')),
            'tanggal' => fake()->date(),
            'user_id' => User::factory(),
            'pelanggan_id' => Pelanggan::factory(),
            'total' => $total,
            'diskon' => $diskon,
            'grand_total' => $total - $diskon,
            'metode_pembayaran' => 'cash',
            'status' => 'pending',
        ];
    }
}
