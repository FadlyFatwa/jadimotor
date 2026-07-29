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
        Schema::create('supplier_inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inquiry_id');
            $table->unsignedBigInteger('id_variasi');
            $table->decimal('qty', 8, 2);
            $table->decimal('harga_penawaran', 12, 2)->nullable();
            $table->enum('status', ['pending', 'selected', 'ordered', 'rejected'])
                ->default('pending');
            $table->date('estimasi_pengiriman')->nullable();
            $table->timestamps();

            $table->foreign('inquiry_id')->references('id')->on('supplier_inquiries')->onDelete('cascade');
            $table->foreign('id_variasi')->references('id_variasi')->on('variasis')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_inquiry_items');
    }
};
