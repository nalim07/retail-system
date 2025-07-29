<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPenjualan extends Model
{
    protected $table = 'riwayat_penjualans';
    protected $fillable = [
        'tanggal_penjualan',
        'nama_barang',
        'jumlah_penjualan',
        'harga_jual',
        'satuan',
        'nama_pelanggan',
    ];
}
