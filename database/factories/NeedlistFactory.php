<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NeedlistFactory extends Factory
{
    protected $model = \App\Models\Needlist::class;

    public function definition(): array
    {
        return [
            'kode_needlist' => 'NL-' . now()->format('Ymd') . '-' . strtoupper(fake()->unique()->bothify('###')),
            'user_id' => User::factory(),
            'status' => 'draft',
            'approval_status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }
}
