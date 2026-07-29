<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SawRekomendasi extends Model
{
    use HasFactory;

    protected $table = 'saw_rekomendasi';

    protected $fillable = [
        'needlist_id',
        'id_variasi',
        'perhitungan_id',
        'supplier_id_saw',
        'supplier_id_dipilih',
        'mengikuti_rekomendasi',
        'nilai_vi_terpilih',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function needlist()
    {
        return $this->belongsTo(Needlist::class, 'needlist_id');
    }

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi');
    }

    public function perhitungan()
    {
        return $this->belongsTo(SawPerhitungan::class, 'perhitungan_id');
    }

    public function supplierSaw()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id_saw', 'id_supplier');
    }

    public function supplierDipilih()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id_dipilih', 'id_supplier');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
