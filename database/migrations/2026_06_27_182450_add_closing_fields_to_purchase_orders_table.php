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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('status');
            $table->boolean('is_force_closed')->default(false)->after('closed_at');
            $table->text('catatan_tutup')->nullable()->after('is_force_closed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['closed_at', 'is_force_closed', 'catatan_tutup']);
        });
    }
};
