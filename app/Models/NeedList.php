<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Needlist extends Model
{
    use HasFactory;

    protected $fillable = ['kode_needlist', 'user_id', 'status', 'approval_notes', 'approved_by', 'approved_at', 'approval_status'];

    // public function items()
    // {
    //     return $this->hasMany(NeedlistItem::class);
    // }

    public function details()
    {
        return $this->hasMany(NeedlistItem::class, 'needlist_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Needlist.php
    public function supplierInquiries()
    {
        return $this->hasMany(SupplierInquiry::class, 'needlist_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'needlist_id');
    }

    public function sawPerhitungan()
    {
        return $this->hasMany(SawPerhitungan::class, 'needlist_id');
    }

    public function sawRekomendasi()
    {
        return $this->hasMany(SawRekomendasi::class, 'needlist_id');
    }
}

