<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptFactory extends Factory
{
    protected $model = \App\Models\Receipt::class;

    public function definition(): array
    {
        return [
            'kode_receipt' => 'RC-' . now()->format('Ymd') . '-' . strtoupper(fake()->unique()->bothify('####')),
            'purchase_order_id' => PurchaseOrder::factory(),
            'tanggal_terima' => now()->format('Y-m-d'),
            'user_id' => User::factory(),
        ];
    }
}
