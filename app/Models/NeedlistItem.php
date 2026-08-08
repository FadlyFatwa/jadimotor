<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeedlistItem extends Model
{
    use HasFactory;

    protected $fillable = ['needlist_id', 'id_variasi', 'qty', 'status', 'rejected_reason', 'keterangan', 'is_reference'];

    protected $casts = ['is_reference' => 'boolean', 'qty' => 'integer'];

    public function needlist()
    {
        return $this->belongsTo(Needlist::class);
    }

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi');
    }

    public function supplierBarang()
    {
        return $this->hasOne(SupplierVariasi::class, 'id_variasi', 'id_variasi');
    }
    

    // NeedlistItem.php
    public function getSupplierAttribute()
    {
        return $this->supplierBarang ? $this->supplierBarang->supplier : null;
    }

}
