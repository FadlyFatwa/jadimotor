<?php

namespace Database\Factories;

use App\Models\Needlist;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class SawPerhitunganFactory extends Factory
{
    protected $model = \App\Models\SawPerhitungan::class;

    public function definition(): array
    {
        return [
            'needlist_id' => Needlist::factory(),
            'id_variasi' => Variasi::factory(),
            'id_barang' => null,
            'tier_key' => null,
            'bobot_snapshot' => [],
            'status' => 'draft',
            'calculated_at' => now(),
            'calculated_by' => null,
        ];
    }
}
