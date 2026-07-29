<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner','supervisor','admin','gudang','akuntan','kasir','procurement') NOT NULL DEFAULT 'admin'");
    }

    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner','admin','gudang','akuntan','kasir','procurement') NOT NULL DEFAULT 'admin'");
    }
};
