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
            $table->foreign('supplier_id')
                ->references('id_supplier')->on('suppliers')->onDelete('cascade');

            $table->date('periode_mulai');
            $table->date('periode_akhir');
            // Nilai per kriteria (C2 dst) tersimpan generik di saw_nilai_historis_detail,
            // bukan kolom fixed di sini — lihat create_saw_nilai_historis_detail_table.
            $table->integer('jumlah_transaksi')->default(0);
            $table->integer('jumlah_transaksi_manual')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saw_nilai_historis');
    }
};
