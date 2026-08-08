<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleGeneration extends Model
{
    use HasFactory;

    protected $fillable = ['vehicle_id', 'code', 'nickname', 'start_year', 'end_year'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function compatibilities()
    {
        return $this->hasMany(ProductVariantCompatibility::class);
    }

    public function variasis()
    {
        return $this->belongsToMany(Variasi::class, 'product_variant_compatibility', 'vehicle_generation_id', 'id_variasi')
            ->withPivot('compatibility_notes', 'is_compatible')
            ->withTimestamps();
    }
}
