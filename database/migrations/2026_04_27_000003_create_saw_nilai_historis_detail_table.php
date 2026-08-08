<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saw_nilai_historis_detail', function (Blueprint $table) {
            $table->id();

            $table->foreignId('historis_id')
                ->constrained('saw_nilai_historis')->cascadeOnDelete();

            $table->foreignId('kriteria_id')
                ->constrained('saw_kriteria')->cascadeOnDelete();

            $table->decimal('nilai', 15, 4);

            $table->timestamps();

            $table->unique(['historis_id', 'kriteria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saw_nilai_historis_detail');
    }
};
