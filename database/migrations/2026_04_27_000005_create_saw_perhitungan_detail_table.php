<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saw_perhitungan_detail', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('perhitungan_id');
            $table->foreign('perhitungan_id')
                ->references('id')->on('saw_perhitungan')->onDelete('cascade');

            $table->unsignedBigInteger('supplier_id');
            $table->foreign('supplier_id')
                ->references('id_supplier')->on('suppliers')->onDelete('cascade');

            $table->unsignedBigInteger('id_variasi')->nullable();
            $table->foreign('id_variasi')
                ->references('id_variasi')->on('variasis')->onDelete('cascade');

            // Rincian nilai per kriteria (Xij, Rij, Wj×Rij), satu baris JSON per kriteria
            // aktif saat perhitungan: {kode: {nilai, norm, weighted}}. Menggantikan pola
            // kolom lebar nilai_c1..c6/norm_c1..c6/weighted_c1..c6 supaya jumlah kriteria
            // bisa berubah (lewat UC-01) tanpa perlu ubah skema tabel lagi.
            $table->json('rincian_kriteria')->nullable();

            $table->decimal('nilai_vi', 10, 6)->default(0); // Skor akhir
            $table->integer('ranking')->default(0);
            $table->tinyInteger('is_recommended')->default(0);

            $table->enum('sumber_c1', ['inquiry', 'historis', 'manual'])->default('inquiry');
            $table->enum('sumber_c3', ['inquiry', 'historis', 'manual'])->default('inquiry');

            // Apakah C2/C4/C5/C6 baris ini dari data historis asli (true) atau
            // hasil mean imputation karena supplier belum punya historis (false).
            $table->boolean('has_historis')->default(false);

            $table->timestamps();

            $table->index(['perhitungan_id', 'ranking']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saw_perhitungan_detail');
    }
};
