<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SawNilaiHistorisDetail extends Model
{
    use HasFactory;

    protected $table = 'saw_nilai_historis_detail';

    protected $fillable = [
        'historis_id',
        'kriteria_id',
        'nilai',
    ];

    public function historis()
    {
        return $this->belongsTo(SawNilaiHistoris::class, 'historis_id');
    }

    public function kriteria()
    {
        return $this->belongsTo(SawKriteria::class, 'kriteria_id');
    }
}
