<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('detail_penerimaans', function (Blueprint $table) {
            $table->id('id_detail_penerimaan'); // Primary Key
            $table->unsignedBigInteger('id_penerimaan'); // Foreign Key ke Penerimaan
            $table->unsignedBigInteger('id_variasi'); // Foreign Key ke Barang
            $table->integer('Jumlah'); // Jumlah Barang Diterima
            $table->decimal('Harga', 15, 2); // Harga Barang saat Penerimaan
            $table->decimal('Total', 15, 2); // Total = Jumlah * Harga
            $table->date('Tanggal'); // Diambil dari Tanggal Nota di Penerimaan
            $table->enum('Status',['disimpan','belum']);
            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_penerimaan')->references('id_penerimaan')->on('penerimaans')->onDelete('cascade');
            $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_penerimaans');
    }
};
