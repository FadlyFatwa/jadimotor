<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saw_nilai_historis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->date('periode_mulai');
            $table->date('periode_akhir');
            $table->decimal('termin_pembayaran', 5, 2)->nullable();      // C2, dalam hari
            $table->decimal('lead_time', 5, 2)->nullable();              // C3, dalam hari
            $table->decimal('lead_time_manual', 5, 2)->nullable();
            $table->decimal('akurasi_kuantitas', 5, 2)->nullable();      // C4, persen
            $table->decimal('akurasi_kuantitas_manual', 5, 2)->nullable();
            $table->decimal('tingkat_pemenuhan', 5, 2)->nullable();      // C5, persen
            $table->decimal('tingkat_pemenuhan_manual', 5, 2)->nullable();
            $table->decimal('komunikasi', 3, 1)->nullable();             // C6, skala 1-5
            $table->integer('jumlah_transaksi')->default(0);
            $table->integer('jumlah_transaksi_manual')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')
                ->references('id_supplier')->on('suppliers')->onDelete('cascade');

            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saw_nilai_historis');
    }
};
