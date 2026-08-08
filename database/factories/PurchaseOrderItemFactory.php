<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\SupplierInquiry;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    protected $model = \App\Models\PurchaseOrderItem::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'inquiry_id' => SupplierInquiry::factory(),
            'id_variasi' => Variasi::factory(),
            'qty_order' => fake()->numberBetween(1, 20),
            'harga_beli' => fake()->numberBetween(8000, 400000),
            'diskon' => null,
            'qty_received' => 0,
        ];
    }
}
