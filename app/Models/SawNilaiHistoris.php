<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SawNilaiHistoris extends Model
{
    use HasFactory;

    protected $table = 'saw_nilai_historis';

    protected $fillable = [
        'supplier_id',
        'periode_mulai',
        'periode_akhir',
        'jumlah_transaksi',
        'jumlah_transaksi_manual',
        'catatan',
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_akhir' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id_supplier');
    }

    public function details()
    {
        return $this->hasMany(SawNilaiHistorisDetail::class, 'historis_id');
    }

}
