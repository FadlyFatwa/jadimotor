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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pr')->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('needlist_id');
            $table->date('tanggal_pr');
            $table->enum('approval_status', ['draft', 'waiting', 'approved', 'rejected'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

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
        Schema::dropIfExists('purchase_requests');
    }
};
