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
        Schema::create('receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id');
            $table->unsignedBigInteger('purchase_order_item_id');
            $table->unsignedBigInteger('id_variasi');
            $table->integer('qty_order');
            $table->integer('qty_received');
            $table->timestamps();

            $table->foreign('receipt_id')
                ->references('id')
                ->on('receipts')
                ->onDelete('cascade');

            $table->foreign('purchase_order_item_id')
                ->references('id')
                ->on('purchase_order_items')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_items');
    }
};
