<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/PenjualanDetail.php
class PenjualanDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_penjualan_detail';

    protected $fillable = [
        'id_penjualan', 'id_variasi', 'nama_barang_jual',
        'harga', 'qty', 'subtotal'
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }

    // Named "barang" for backward compatibility with existing controller/view
    // eager-loads (details.barang), but points at the real Variasi model —
    // App\Models\Barang does not exist in this codebase.
    public function barang()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi');
    }
}

