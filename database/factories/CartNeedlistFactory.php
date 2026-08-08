<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartNeedlistFactory extends Factory
{
    protected $model = \App\Models\CartNeedlist::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'id_variasi' => Variasi::factory(),
            'qty' => fake()->numberBetween(1, 10),
        ];
    }
}
