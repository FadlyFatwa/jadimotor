<?php

namespace Database\Factories;

use App\Models\PurchaseOrderItem;
use App\Models\Receipt;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptItemFactory extends Factory
{
    protected $model = \App\Models\ReceiptItem::class;

    public function definition(): array
    {
        return [
            'receipt_id' => Receipt::factory(),
            'purchase_order_item_id' => PurchaseOrderItem::factory(),
            'id_variasi' => Variasi::factory(),
            'qty_order' => fake()->numberBetween(1, 20),
            'qty_received' => fake()->numberBetween(1, 20),
        ];
    }
}
