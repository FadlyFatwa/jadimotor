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
        Schema::create('supplier_barang', function (Blueprint $table) {
            $table->id('id_supplier_variasi');
            $table->unsignedBigInteger('id_variasi');
            $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
            $table->unsignedBigInteger('id_supplier');
            $table->foreign('id_supplier')->references('id_supplier')->on('suppliers')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('harga_list');
            $table->string('kode_list', 20);
            $table->integer('harga_beli');
            $table->string('kode_beli', 20);
            $table->decimal('diskon',3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_barang');
    }
};
