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
        Schema::create('needlist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('needlist_id');
            $table->unsignedBigInteger('id_variasi');
            $table->integer('qty');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejected_reason')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_reference')->default(false);
            $table->timestamps();

            $table->foreign('needlist_id')->references('id')->on('needlists')->onDelete('cascade');
            $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('needlist_items');
    }
};
