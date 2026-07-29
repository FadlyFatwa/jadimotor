<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierInquiryItem extends Model
{
    protected $fillable = ['inquiry_id', 'id_variasi', 'qty','status', 'harga_penawaran', 'estimasi_pengiriman'];

    protected $casts = ['qty' => 'integer'];

    public function inquiry()
    {
        return $this->belongsTo(SupplierInquiry::class, 'inquiry_id');
    }

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi');
    }
}

