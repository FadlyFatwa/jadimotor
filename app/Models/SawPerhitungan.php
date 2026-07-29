<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SawPerhitungan extends Model
{
    use HasFactory;

    protected $table = 'saw_perhitungan';

    protected $fillable = [
        'needlist_id',
        'id_variasi',
        'id_barang',
        'tier_key',
        'bobot_snapshot',
        'status',
        'calculated_at',
        'calculated_by',
    ];

    protected $casts = [
        'bobot_snapshot' => 'array',
        'calculated_at'  => 'datetime',
    ];

    public function needlist()
    {
        return $this->belongsTo(Needlist::class, 'needlist_id');
    }

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi');
    }

    public function mBarang()
    {
        return $this->belongsTo(MBarang::class, 'id_barang', 'id_barang');
    }

    public function details()
    {
        return $this->hasMany(SawPerhitunganDetail::class, 'perhitungan_id');
    }

    public function calculatedBy()
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function rekomendasi()
    {
        return $this->hasOne(SawRekomendasi::class, 'perhitungan_id');
    }
}
