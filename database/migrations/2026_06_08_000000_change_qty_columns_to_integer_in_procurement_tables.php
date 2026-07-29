<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // needlist_items.qty sudah INT sejak create migration-nya (sudah digabung).
        DB::statement('ALTER TABLE supplier_inquiry_items MODIFY qty INT NOT NULL');
        DB::statement('ALTER TABLE purchase_request_items MODIFY qty INT NOT NULL');
        DB::statement('ALTER TABLE purchase_order_items MODIFY qty_order INT NOT NULL');
        DB::statement('ALTER TABLE purchase_order_items MODIFY qty_received INT NOT NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE supplier_inquiry_items MODIFY qty DECIMAL(8,2) NOT NULL');
        DB::statement('ALTER TABLE purchase_request_items MODIFY qty DECIMAL(8,2) NOT NULL');
        DB::statement('ALTER TABLE purchase_order_items MODIFY qty_order DECIMAL(8,2) NOT NULL');
        DB::statement('ALTER TABLE purchase_order_items MODIFY qty_received DECIMAL(8,2) NOT NULL DEFAULT 0');
    }
};
