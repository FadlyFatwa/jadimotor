<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'id_variasi',
        'nama_barang_jual',
        'harga',
        'diskon',
        'qty',
        'subtotal'
    ];

    protected $appends = ['barcode']; // Menambahkan accessor untuk barcode

    // Relasi ke barang
    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi');
    }

    // Accessor untuk barcode
    public function getBarcodeAttribute()
    {
        return $this->barang->barcode ?? '';
    }

    // Hitung subtotal
    public function calculateSubtotal()
    {
        $this->subtotal = ($this->harga_jual - $this->diskon) * $this->qty;
        $this->save();
    }
}