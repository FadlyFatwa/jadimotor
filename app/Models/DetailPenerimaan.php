<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenerimaan extends Model
{
    use HasFactory;

    // Nama tabel
    protected $table = 'detail_penerimaans';

    // Primary key kustom
    protected $primaryKey = 'ID_detail_penerimaan';

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'ID_Penerimaan',
        'ID_Barang',
        'Jumlah',
        'Harga',
        'Total',
        'Tanggal',
        'Status',
    ];

    // Relasi ke tabel penerimaans
    public function penerimaan()
    {
        return $this->belongsTo(Penerimaan::class, 'ID_Penerimaan', 'ID_Penerimaan');
    }

    // Relasi ke tabel barangs
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'ID_Barang', 'ID_Barang');
    }

    public function supplier()
    {
    return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }
}