<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $fillable = [
        'kode_receipt',
        'purchase_order_id',
        'tanggal_terima',
        'user_id',
    ];

    public function items()
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

