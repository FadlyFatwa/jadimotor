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
        Schema::create('penerimaans', function (Blueprint $table) {
            $table->id('id_penerimaan'); // Primary Key
            $table->unsignedBigInteger('id_supplier'); // Foreign Key ke Supplier
            $table->string('Invoice'); // Nomor Invoice
            $table->date('Tanggal_Nota'); // Tanggal Nota
            $table->date('Tanggal_Datang'); // Tanggal Barang Datang
            $table->date('Jatuh_Tempo'); // Tanggal Jatuh Tempo dari supplier 
            $table->decimal('Total', 15, 2); // Total dari detail penerimaan
            $table->decimal('PPN', 15, 2)->nullable(); // PPN (Opsional)
            $table->decimal('Grand_Total', 15, 2); //Hasil Dari Total + PPN(jika ada)
            $table->enum('status',['lunas', 'belum lunas'])->default('lunas');
            $table->timestamps();

            // Foreign Key
            $table->foreign('id_supplier')->references('id_supplier')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaans');
    }
};
