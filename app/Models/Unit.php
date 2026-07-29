<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    // Primary key sesuai dengan skema
    protected $primaryKey = 'id_unit';

    // Kolom yang dapat diisi secara mass assignment
    protected $fillable = [
        'kode_unit',
        'nama_unit',
    ];

    // Relasi dengan Barang
    public function m_barangs()
    {
        return $this->hasMany(MBarang::class, 'id_unit', 'id_unit');
    }
}