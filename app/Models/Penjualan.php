<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $fillable = [
        'nomor_nota', 'tanggal', 'user_id', 'pelanggan_id',
        'total', 'diskon', 'grand_total', 'metode_pembayaran', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function details()
    {
        return $this->hasMany(PenjualanDetail::class);
    }
}
