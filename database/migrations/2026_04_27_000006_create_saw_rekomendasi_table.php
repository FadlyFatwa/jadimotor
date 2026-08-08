<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saw_rekomendasi', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('needlist_id');
            $table->foreign('needlist_id')
                ->references('id')->on('needlists')->onDelete('cascade');

            $table->unsignedBigInteger('id_variasi');
            $table->foreign('id_variasi')
                ->references('id_variasi')->on('variasis')->onDelete('cascade');

            $table->unsignedBigInteger('perhitungan_id');
            $table->foreign('perhitungan_id')
                ->references('id')->on('saw_perhitungan')->onDelete('cascade');

            $table->unsignedBigInteger('supplier_id_saw');       // rekomendasi SAW
            $table->foreign('supplier_id_saw')
                ->references('id_supplier')->on('suppliers')->onDelete('cascade');

            $table->unsignedBigInteger('supplier_id_dipilih')->nullable(); // pilihan user
            $table->foreign('supplier_id_dipilih')
                ->references('id_supplier')->on('suppliers')->onDelete('set null');

            $table->tinyInteger('mengikuti_rekomendasi')->nullable()->default(0);
            $table->decimal('nilai_vi_terpilih', 10, 6)->nullable();
            $table->timestamp('confirmed_at')->nullable();

            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->foreign('confirmed_by')
                ->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->unique(['needlist_id', 'id_variasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saw_rekomendasi');
    }
};
