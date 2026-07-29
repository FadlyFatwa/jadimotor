<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categorization_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_variasi')->nullable();
            $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('set null');
            $table->string('barcode', 50);

            $table->string('nama_variasi_lama', 100);
            $table->string('nama_variasi_baru', 100);

            $table->unsignedBigInteger('id_barang_baru')->nullable();
            $table->foreign('id_barang_baru')->references('id_barang')->on('m_barangs')->onDelete('set null');
            $table->string('part_number_baru')->nullable();

            $table->unsignedBigInteger('dikategorikan_oleh')->nullable();
            $table->foreign('dikategorikan_oleh')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('dikategorikan_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_categorization_logs');
    }
};
