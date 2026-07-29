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
        Schema::create('needlists', function (Blueprint $table) {
            $table->id();
            $table->string('kode_needlist')->unique();
            $table->unsignedBigInteger('user_id');
            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'rejected',
                'inquiry_created',
                'selection_in_progress',
                'po_issued',
                'completed',
            ])->default('draft');
            $table->enum('approval_status', ['draft', 'waiting', 'approved', 'rejected'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable(); // User ID
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('needlists');
    }
};
