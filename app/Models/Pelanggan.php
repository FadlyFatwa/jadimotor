<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Pelanggan.php
class Pelanggan extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'email', 'telepon', 'alamat'];

    public function penjualans()
    {
        return $this->hasMany(Penjualan::class);
    }
}
