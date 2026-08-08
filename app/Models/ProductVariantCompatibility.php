<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantCompatibility extends Model
{
    use HasFactory;

    protected $table = 'product_variant_compatibility';

    protected $fillable = [
        'id_variasi',
        'vehicle_generation_id',
        'compatibility_notes',
        'is_compatible',
    ];

    protected $casts = [
        'is_compatible' => 'boolean',
    ];

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi');
    }

    public function vehicleGeneration()
    {
        return $this->belongsTo(VehicleGeneration::class);
    }
}
