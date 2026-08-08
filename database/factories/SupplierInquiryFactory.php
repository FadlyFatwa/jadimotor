<?php

namespace Database\Factories;

use App\Models\Needlist;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInquiryFactory extends Factory
{
    protected $model = \App\Models\SupplierInquiry::class;

    public function definition(): array
    {
        return [
            'needlist_id' => Needlist::factory(),
            'supplier_id' => Supplier::factory(),
            'status' => 'waiting_response',
            'catatan' => null,
        ];
    }
}
