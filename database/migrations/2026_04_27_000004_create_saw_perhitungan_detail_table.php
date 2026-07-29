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
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('id_variasi')->nullable();

            // Nilai mentah (Xij)
            $table->decimal('nilai_c1', 15, 6)->default(0); // Total Biaya
            $table->decimal('nilai_c2', 15, 6)->default(0); // Termin Pembayaran
            $table->decimal('nilai_c3', 15, 6)->default(0); // Lead Time
            $table->decimal('nilai_c4', 15, 6)->default(0); // Akurasi Kuantitas
            $table->decimal('nilai_c5', 15, 6)->default(0); // Tingkat Pemenuhan
            $table->decimal('nilai_c6', 15, 6)->default(0); // Komunikasi

            // Nilai normalisasi (Rij)
            $table->decimal('norm_c1', 10, 6)->default(0);
            $table->decimal('norm_c2', 10, 6)->default(0);
            $table->decimal('norm_c3', 10, 6)->default(0);
            $table->decimal('norm_c4', 10, 6)->default(0);
            $table->decimal('norm_c5', 10, 6)->default(0);
            $table->decimal('norm_c6', 10, 6)->default(0);

            // Nilai terbobot (Wj × Rij)
            $table->decimal('weighted_c1', 10, 6)->default(0);
            $table->decimal('weighted_c2', 10, 6)->default(0);
            $table->decimal('weighted_c3', 10, 6)->default(0);
            $table->decimal('weighted_c4', 10, 6)->default(0);
            $table->decimal('weighted_c5', 10, 6)->default(0);
            $table->decimal('weighted_c6', 10, 6)->default(0);

            $table->decimal('nilai_vi', 10, 6)->default(0); // Skor akhir
            $table->integer('ranking')->default(0);
            $table->tinyInteger('is_recommended')->default(0);

            $table->enum('sumber_c1', ['inquiry', 'historis', 'manual'])->default('inquiry');
            $table->enum('sumber_c3', ['inquiry', 'historis', 'manual'])->default('inquiry');

            // Apakah C2/C4/C5/C6 baris ini dari data historis asli (true) atau
            // hasil mean imputation karena supplier belum punya historis (false).
            $table->boolean('has_historis')->default(false);

            $table->timestamps();

            $table->foreign('perhitungan_id')
                ->references('id')->on('saw_perhitungan')->onDelete('cascade');
            $table->foreign('supplier_id')
                ->references('id_supplier')->on('suppliers')->onDelete('cascade');
            $table->foreign('id_variasi')
                ->references('id_variasi')->on('variasis')->onDelete('cascade');

            $table->index(['perhitungan_id', 'ranking']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saw_perhitungan_detail');
    }
};
