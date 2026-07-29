<?php

namespace App\Models;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Penerimaan extends Model
{
    use HasFactory;

    // Nama tabel
    protected $table = 'penerimaans';

    // Primary key kustom
    protected $primaryKey = 'ID_Penerimaan';

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'id_unit',
        'Invoice',
        'Tanggal_Nota',
        'Tanggal_Datang',
        'Jatuh_Tempo',
        'Total',
        'PPN',
        'Grand_Total',
        'status',
    ];

    // Relasi ke tabel suppliers
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_unit', 'id_unit');
    }

    // Relasi ke tabel detail_penerimaans
    public function details()
    {
        return $this->hasMany(DetailPenerimaan::class, 'ID_Penerimaan', 'ID_Penerimaan');
    }
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'id_unit');
    }

}