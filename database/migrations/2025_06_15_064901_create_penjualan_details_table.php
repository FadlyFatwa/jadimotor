<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // database/migrations/xxxx_xx_xx_create_penjualan_details_table.php
        Schema::create('penjualan_details', function (Blueprint $table) {
            $table->id('id_penjualan_detail');
            $table->unsignedBigInteger('id_penjualan');
            $table->foreign('id_penjualan')->references('id_penjualan')->on('penjualans')->onDelete('cascade');
            $table->unsignedBigInteger('id_variasi');
            $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
            $table->string('nama_barang_jual'); // bisa berbeda dari master
            $table->decimal('harga', 12, 2);
            $table->integer('qty');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_details');
    }
};
