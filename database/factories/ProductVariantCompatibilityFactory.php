<?php

namespace Database\Factories;

use App\Models\Variasi;
use App\Models\VehicleGeneration;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantCompatibilityFactory extends Factory
{
    protected $model = \App\Models\ProductVariantCompatibility::class;

    public function definition(): array
    {
        return [
            'id_variasi' => Variasi::factory(),
            'vehicle_generation_id' => VehicleGeneration::factory(),
            'compatibility_notes' => fake()->sentence(),
            'is_compatible' => true,
        ];
    }
}
