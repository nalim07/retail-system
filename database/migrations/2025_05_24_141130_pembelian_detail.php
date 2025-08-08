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
        Schema::create('pembelian_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pembelian')->constrained('pembelian')->onDelete('cascade');
            $table->foreignId('id_barang')->nullable()->constrained('barang')->onDelete('cascade');
            $table->integer('jumlah_pembelian');
            $table->string('satuan')->nullable();
            $table->integer('sisa');
            $table->unsignedBigInteger('harga_beli')->nullable();
            $table->unsignedBigInteger('harga_jual')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_detail');
    }
};
