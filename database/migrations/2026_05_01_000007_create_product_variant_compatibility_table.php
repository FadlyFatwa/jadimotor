<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_compatibility', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_variasi');
            $table->foreignId('vehicle_generation_id')->constrained('vehicle_generations')->cascadeOnDelete();
            $table->text('compatibility_notes')->nullable();
            $table->boolean('is_compatible')->default(true);
            $table->timestamps();

            $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->cascadeOnDelete();
            $table->unique(['id_variasi', 'vehicle_generation_id'], 'pvc_variasi_gen_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_compatibility');
    }
};
