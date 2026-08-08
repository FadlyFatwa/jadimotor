<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategorizationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_variasi',
        'barcode',
        'nama_variasi_lama',
        'nama_variasi_baru',
        'id_barang_baru',
        'part_number_baru',
        'dikategorikan_oleh',
        'dikategorikan_at',
    ];

    protected $casts = [
        'dikategorikan_at' => 'datetime',
    ];

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi');
    }

    public function mbarang()
    {
        return $this->belongsTo(MBarang::class, 'id_barang_baru', 'id_barang');
    }

    public function dikategorikanOlehUser()
    {
        return $this->belongsTo(User::class, 'dikategorikan_oleh');
    }
}
