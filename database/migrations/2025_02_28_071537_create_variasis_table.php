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
        Schema::create('variasis', function (Blueprint $table) {
            $table->id('id_variasi'); // Hanya satu AUTO_INCREMENT
            $table->string('barcode', 50);
            $table->string('nama_variasi', 100);
            $table->string('part_number')->nullable();

            $table->unsignedBigInteger('id_barang');
            $table->foreign('id_barang')->references('id_barang')->on('m_barangs')->onDelete('cascade')->onUpdate('cascade');

            // Foreign keys
            $table->unsignedBigInteger('id_unit');
            $table->foreign('id_unit')->references('id_unit')->on('units')->onDelete('cascade')->onUpdate('cascade');

            $table->decimal('harga_jual', 15, 2);
            $table->decimal('stock', 8, 2);
            $table->enum('status', ['active', 'nonactive'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->enum('tier', [
                'OEM',
                'Original',
                'Aftermarket',
                'Aftermarket A',
                'Aftermarket B',
                'Aftermarket C',
                'KW',
                'Lelangan',
            ])->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variasis');
    }
};
