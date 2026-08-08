<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDuplicateMerge extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id_variasi',
        'target_barcode',
        'merged_id_variasi',
        'merged_barcode',
        'merged_nama_variasi',
        'stock_moved',
        'merged_by',
        'merged_at',
    ];

    protected $casts = [
        'merged_at' => 'datetime',
    ];

    public function target()
    {
        return $this->belongsTo(Variasi::class, 'target_id_variasi', 'id_variasi');
    }

    public function merged()
    {
        return $this->belongsTo(Variasi::class, 'merged_id_variasi', 'id_variasi');
    }

    public function mergedByUser()
    {
        return $this->belongsTo(User::class, 'merged_by');
    }
}
