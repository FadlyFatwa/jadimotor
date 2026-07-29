<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    protected $fillable = ['purchase_request_id', 'id_variasi', 'qty', 'harga_list', 'harga_beli', 'diskon'];

    protected $casts = ['qty' => 'integer'];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi');
    }
}

