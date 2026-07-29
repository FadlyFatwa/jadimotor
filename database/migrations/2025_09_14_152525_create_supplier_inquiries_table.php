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
        Schema::create('supplier_inquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('needlist_id');
            $table->unsignedBigInteger('supplier_id');
            $table->enum('status', ['waiting_response', 'responded', 'rejected'])->default('waiting_response');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('needlist_id')->references('id')->on('needlists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_inquiries');
    }
};
