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
        // database/migrations/xxxx_xx_xx_create_penjualans_table.php
        Schema::create('penjualans', function (Blueprint $table) {
            $table->id('id_penjualan');
            $table->string('nomor_nota', 50)->unique();
            $table->date('tanggal');

            // Foreign keys
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('pelanggan_id')->nullable();
            $table->foreign('pelanggan_id')->references('id')->on('pelanggans')->onDelete('cascade')->onUpdate('cascade');

            $table->decimal('total', 10, 2);
            $table->decimal('diskon', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->string('metode_pembayaran', 50);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualans');
    }
};
