<?php

namespace Database\Factories;

use App\Models\Needlist;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = \App\Models\PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'kode_po' => 'PO-' . now()->format('Ymd') . '-' . strtoupper(fake()->unique()->bothify('####')),
            'supplier_id' => Supplier::factory(),
            'needlist_id' => Needlist::factory(),
            'tanggal_po' => now()->format('Y-m-d'),
            'status' => 'open',
            'closed_at' => null,
            'is_force_closed' => false,
            'catatan_tutup' => null,
        ];
    }
}
