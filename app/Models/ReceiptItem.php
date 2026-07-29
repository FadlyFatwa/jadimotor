<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptItem extends Model
{
    protected $fillable = ['receipt_id', 'purchase_order_item_id', 'id_variasi', 'qty_order', 'qty_received'];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi');
    }
}
