<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saw_kriteria', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();       // C1, C2, C3, C4, C5, C6
            $table->string('nama', 100);
            $table->enum('jenis', ['cost', 'benefit']);
            $table->decimal('bobot', 5, 4);             // 0.0000 – 0.9999
            $table->string('satuan', 30)->nullable();   // Rp, %, Hari, Skala
            $table->tinyInteger('is_active')->default(1);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saw_kriteria');
    }
};
