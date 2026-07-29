<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SawKriteria extends Model
{
    use HasFactory;

    protected $table = 'saw_kriteria';

    protected $fillable = [
        'kode',
        'nama',
        'jenis',
        'bobot',
        'satuan',
        'is_active',
        'urutan',
    ];

    /**
     * Ambil hanya kriteria yang aktif, urut berdasarkan kolom urutan.
     */
    public function scopeAktif($query)
    {
        return $query->where('is_active', 1)->orderBy('urutan');
    }

    public function isCost(): bool
    {
        return $this->jenis === 'cost';
    }

    public function isBenefit(): bool
    {
        return $this->jenis === 'benefit';
    }
}
