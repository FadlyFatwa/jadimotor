<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'kode_po', 'needlist_id', 'supplier_id', 'tanggal_po', 'status',
        'closed_at', 'is_force_closed', 'catatan_tutup',
    ];

    protected $casts = [
        'closed_at'       => 'datetime',
        'is_force_closed' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function needlist()
    {
        return $this->belongsTo(Needlist::class, 'needlist_id');
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function receiptItems()
    {
        return $this->hasMany(ReceiptItem::class);
    }

}

