<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up()
    {
        Schema::table('supplier_inquiry_items', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_inquiry_items', 'status')) {
                $table->enum('status', ['pending', 'selected', 'ordered', 'rejected'])
                    ->default('pending')
                    ->after('harga_penawaran');
            }
        });
    }

    public function down()
    {
        Schema::table('supplier_inquiry_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

};
