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
        Schema::create('penjualan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_penjualan')->constrained('penjualan')->onDelete('cascade');
            $table->foreignId('id_barang')->nullable()->constrained('barang')->onDelete('cascade');
            $table->foreignId('id_pembelian_detail')->nullable()->constrained('pembelian_detail')->onDelete('cascade');
            $table->integer('jumlah_penjualan');
            $table->unsignedBigInteger('harga_jual')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_detail');
    }
};
