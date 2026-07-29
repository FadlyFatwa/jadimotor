<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierVariasi extends Model
{
    use HasFactory;

    protected $table = 'supplier_barang';
    protected $primaryKey = 'id_supplier_variasi';

    protected $fillable = [
        'id_variasi',
        'id_supplier',
        'harga_list',
        'kode_list',
        'harga_beli',
        'kode_beli',
        'diskon',
    ];

    // Relasi ke model Variasi
    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi');
    }

    // Relasi ke model Supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }
}
