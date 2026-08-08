<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleGenerationFactory extends Factory
{
    protected $model = \App\Models\VehicleGeneration::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'code' => strtoupper(fake()->bothify('GEN-##??')),
            'nickname' => fake()->word(),
            'start_year' => 2010,
            'end_year' => 2020,
        ];
    }
}
