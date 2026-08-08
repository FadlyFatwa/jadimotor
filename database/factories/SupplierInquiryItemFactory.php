<?php

namespace Database\Factories;

use App\Models\SupplierInquiry;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInquiryItemFactory extends Factory
{
    protected $model = \App\Models\SupplierInquiryItem::class;

    public function definition(): array
    {
        return [
            'inquiry_id' => SupplierInquiry::factory(),
            'id_variasi' => Variasi::factory(),
            'qty' => fake()->numberBetween(1, 20),
            'harga_penawaran' => null,
            'status' => 'pending',
            'estimasi_pengiriman' => null,
        ];
    }
}
