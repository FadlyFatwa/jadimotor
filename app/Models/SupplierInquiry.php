<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierInquiry extends Model
{
    use HasFactory;

    protected $fillable = ['needlist_id', 'supplier_id', 'status', 'catatan'];

    public function needlist()
    {
        return $this->belongsTo(Needlist::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(SupplierInquiryItem::class, 'inquiry_id');
    }
}
