<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/Pelanggan.php
class Pelanggan extends Model
{
    protected $fillable = ['nama', 'email', 'telepon', 'alamat'];

    public function penjualans()
    {
        return $this->hasMany(Penjualan::class);
    }
}
