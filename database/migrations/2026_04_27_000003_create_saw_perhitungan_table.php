<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saw_perhitungan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('needlist_id');
            $table->unsignedBigInteger('id_variasi')->nullable();  // null jika SAW per master barang
            $table->unsignedBigInteger('id_barang')->nullable();   // diisi jika SAW per master barang

            // Hash unik per kombinasi cluster+tier dalam satu master barang.
            // Value = md5(sorted variasi_ids) — membedakan OEM vs Original vs KW.
            $table->char('tier_key', 32)->nullable();

            $table->json('bobot_snapshot');
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->timestamp('calculated_at')->nullable();
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->timestamps();

            $table->foreign('needlist_id')
                ->references('id')->on('needlists')->onDelete('cascade');
            $table->foreign('id_variasi')
                ->references('id_variasi')->on('variasis')->onDelete('cascade');
            $table->foreign('id_barang')
                ->references('id_barang')->on('m_barangs')->onDelete('cascade');
            $table->foreign('calculated_by')
                ->references('id')->on('users')->onDelete('set null');

            $table->index(['needlist_id', 'id_barang']);
            $table->index(['needlist_id', 'id_variasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saw_perhitungan');
    }
};
