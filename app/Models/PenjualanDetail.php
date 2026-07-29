<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/PenjualanDetail.php
class PenjualanDetail extends Model
{
    protected $fillable = [
        'penjualan_id', 'ID_Barang', 'nama_barang_jual',
        'harga', 'qty', 'subtotal'
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}

