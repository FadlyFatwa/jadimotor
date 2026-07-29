<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variasi extends Model
{
    use HasFactory;

    // Primary key sesuai dengan skema
    protected $primaryKey = 'id_variasi';

    protected $fillable = [
        'barcode',
        'id_barang',
        'nama_variasi',
        'id_unit',
        'harga_jual',
        'stock',
        'status',
        'part_number',
        'is_active',
        'tier',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function m_barang()
    {
        return $this->belongsTo(MBarang::class, 'id_barang');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'id_unit');
    }

    public function suppliervariasi()
    {
        return $this->hasMany(SupplierVariasi::class, 'id_variasi', 'id_variasi');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'id_variasi', 'id_variasi');
    }

    public function cartNeedlists()
    {
        return $this->hasMany(CartNeedlist::class, 'id_variasi');
    }

    public function needlistItems()
    {
        return $this->hasMany(NeedlistItem::class, 'id_variasi');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'id_variasi');
    }

    public function compatibilities()
    {
        return $this->hasMany(ProductVariantCompatibility::class, 'id_variasi', 'id_variasi');
    }

    public function vehicleGenerations()
    {
        return $this->belongsToMany(VehicleGeneration::class, 'product_variant_compatibility', 'id_variasi', 'vehicle_generation_id')
            ->withPivot('compatibility_notes', 'is_compatible')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}