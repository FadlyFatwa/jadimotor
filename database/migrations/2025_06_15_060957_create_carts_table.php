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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('id_variasi');
            $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
            $table->string('nama_barang_jual')->nullable();
            $table->integer('qty')->nullable(); // Perbaikan: Parameter kedua dihapus
            $table->decimal('harga', 10, 2)->nullable();
            $table->decimal('diskon', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->timestamps();

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
