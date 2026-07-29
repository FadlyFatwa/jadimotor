<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    // Primary key sesuai dengan skema
    protected $primaryKey = 'id_supplier';

    // Kolom yang dapat diisi secara mass assignment
    protected $fillable = [
        'kode_supplier',
        'nama_supplier',
        'no_telp',
        'alamat',
    ];

    // Relasi dengan Barang
    public function variasis()
    {
        return $this->hasMany(Variasi::class, 'id_supplier', 'id_supplier');
    }
}