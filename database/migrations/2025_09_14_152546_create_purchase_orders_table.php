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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('kode_po')->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('needlist_id');
            $table->date('tanggal_po');
            $table->enum('status', ['open', 'partial_received', 'completed', 'cancelled'])->default('open');
            $table->timestamps();

            $table->foreign('supplier_id')->references('id_supplier')->on('suppliers')->onDelete('cascade');
            $table->foreign('needlist_id')->references('id')->on('needlists')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
