<?php

namespace Database\Factories;

use App\Models\SawPerhitungan;
use App\Models\Supplier;
use App\Models\Variasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class SawPerhitunganDetailFactory extends Factory
{
    protected $model = \App\Models\SawPerhitunganDetail::class;

    public function definition(): array
    {
        return [
            'perhitungan_id' => SawPerhitungan::factory(),
            'supplier_id' => Supplier::factory(),
            'id_variasi' => Variasi::factory(),

            'rincian_kriteria' => collect(['C1', 'C2', 'C3', 'C4', 'C5', 'C6'])
                ->mapWithKeys(fn ($kode) => [$kode => ['nilai' => 0, 'norm' => 0, 'weighted' => 0]])
                ->all(),

            'nilai_vi' => 0,
            'ranking' => 0,
            'is_recommended' => 0,
            'sumber_c1' => 'inquiry',
            'sumber_c3' => 'inquiry',
            'has_historis' => false,
        ];
    }
}
