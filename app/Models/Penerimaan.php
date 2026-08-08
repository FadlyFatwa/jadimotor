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

    // Primary key kustom (matches the actual `id_penerimaan` column from the
    // create_penerimaans_table migration — was previously "ID_Penerimaan",
    // which never matched a real column).
    protected $primaryKey = 'id_penerimaan';

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'id_supplier',
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
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    // Relasi ke tabel detail_penerimaans
    public function details()
    {
        return $this->hasMany(DetailPenerimaan::class, 'id_penerimaan', 'id_penerimaan');
    }

}