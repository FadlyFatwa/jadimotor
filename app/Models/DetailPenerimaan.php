<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenerimaan extends Model
{
    use HasFactory;

    // Nama tabel
    protected $table = 'detail_penerimaans';

    // Primary key kustom (matches the actual `id_detail_penerimaan` column;
    // was previously "ID_detail_penerimaan", which never matched a real column).
    protected $primaryKey = 'id_detail_penerimaan';

    // Kolom yang dapat diisi secara massal (id_penerimaan/id_variasi are the
    // real FK columns from the migration — the old fillable used
    // "ID_Penerimaan"/"ID_Barang" which don't exist as columns).
    protected $fillable = [
        'id_penerimaan',
        'id_variasi',
        'Jumlah',
        'Harga',
        'Total',
        'Tanggal',
        'Status',
    ];

    // Relasi ke tabel penerimaans
    public function penerimaan()
    {
        return $this->belongsTo(Penerimaan::class, 'id_penerimaan', 'id_penerimaan');
    }

    // Relasi ke variasi (App\Models\Barang does not exist in this codebase).
    public function barang()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi');
    }
}