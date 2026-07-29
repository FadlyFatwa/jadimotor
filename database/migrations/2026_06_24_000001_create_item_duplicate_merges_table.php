<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_duplicate_merges', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('target_id_variasi')->nullable();
            $table->foreign('target_id_variasi')->references('id_variasi')->on('variasis')->onDelete('set null');
            $table->string('target_barcode', 50);

            $table->unsignedBigInteger('merged_id_variasi')->nullable();
            $table->foreign('merged_id_variasi')->references('id_variasi')->on('variasis')->onDelete('set null');
            $table->string('merged_barcode', 50);
            $table->string('merged_nama_variasi', 100);

            $table->decimal('stock_moved')->default(0);

            $table->unsignedBigInteger('merged_by')->nullable();
            $table->foreign('merged_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('merged_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_duplicate_merges');
    }
};
