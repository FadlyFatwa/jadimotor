<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SawPerhitunganDetail extends Model
{
    use HasFactory;

    protected $table = 'saw_perhitungan_detail';

    protected $fillable = [
        'perhitungan_id',
        'supplier_id',
        'id_variasi',
        // Nilai mentah
        'nilai_c1', 'nilai_c2', 'nilai_c3', 'nilai_c4', 'nilai_c5', 'nilai_c6',
        // Normalisasi
        'norm_c1', 'norm_c2', 'norm_c3', 'norm_c4', 'norm_c5', 'norm_c6',
        // Terbobot
        'weighted_c1', 'weighted_c2', 'weighted_c3', 'weighted_c4', 'weighted_c5', 'weighted_c6',
        'nilai_vi',
        'ranking',
        'is_recommended',
        'sumber_c1',
        'sumber_c3',
        'has_historis',
    ];

    public function perhitungan()
    {
        return $this->belongsTo(SawPerhitungan::class, 'perhitungan_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id_supplier');
    }

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi');
    }
}
