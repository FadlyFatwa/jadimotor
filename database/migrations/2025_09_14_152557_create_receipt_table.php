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
            Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('kode_receipt')->unique();
            $table->unsignedBigInteger('purchase_order_id');
            $table->date('tanggal_terima');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receivings');
    }
};
