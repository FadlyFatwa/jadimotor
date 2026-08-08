<?php

namespace Database\Factories;

use App\Models\Needlist;
use App\Models\SawPerhitungan;
use App\Models\Supplier;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class SawRekomendasiFactory extends Factory
{
    protected $model = \App\Models\SawRekomendasi::class;

    public function definition(): array
    {
        return [
            'needlist_id' => Needlist::factory(),
            'id_variasi' => Variasi::factory(),
            'perhitungan_id' => SawPerhitungan::factory(),
            'supplier_id_saw' => Supplier::factory(),
            'supplier_id_dipilih' => null,
            'mengikuti_rekomendasi' => 0,
            'nilai_vi_terpilih' => null,
            'confirmed_at' => null,
            'confirmed_by' => null,
        ];
    }
}
