<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_order_id','inquiry_id', 'id_variasi', 'qty_order', 'harga_beli', 'diskon', 'qty_received'];

    protected $casts = ['qty_order' => 'integer', 'qty_received' => 'integer'];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi');
    }
    
    public function inquiry()
    {
        return $this->belongsTo(SupplierInquiry::class, 'inquiry_id');
    }

    public function receiptItems()
    {
        return $this->hasMany(ReceiptItem::class);
    }

}

