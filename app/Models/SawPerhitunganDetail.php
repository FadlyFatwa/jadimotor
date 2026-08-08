<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SawPerhitunganDetail extends Model
{
    use HasFactory;

    protected $table = 'saw_perhitungan_detail';

    protected $fillable = [
        'perhitungan_id',
        'supplier_id',
        'id_variasi',
        // Snapshot dinamis: ['C1' => ['nilai'=>x,'norm'=>x,'weighted'=>x], 'C2' => [...], ...]
        // — mengikuti pola bobot_snapshot di SawPerhitungan, supaya jumlah kriteria
        // bisa berubah (tambah/hapus) tanpa perlu migrasi skema tabel lagi.
        'rincian_kriteria',
        'nilai_vi',
        'ranking',
        'is_recommended',
        'sumber_c1',
        'sumber_c3',
        'has_historis',
    ];

    protected $casts = [
        'rincian_kriteria' => 'array',
        'is_recommended'   => 'boolean',
        'has_historis'     => 'boolean',
    ];

    public function nilai(string $kode): float
    {
        return (float) ($this->rincian_kriteria[$kode]['nilai'] ?? 0);
    }

    public function norm(string $kode): float
    {
        return (float) ($this->rincian_kriteria[$kode]['norm'] ?? 0);
    }

    public function weighted(string $kode): float
    {
        return (float) ($this->rincian_kriteria[$kode]['weighted'] ?? 0);
    }

    public function perhitungan()
    {
        return $this->belongsTo(SawPerhitungan::class, 'perhitungan_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id_supplier');
    }

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi', 'id_variasi');
    }
}
