<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPembelian extends Model
{
    protected $table = 'riwayat_pembelians';
    protected $fillable = [
        'tanggal_pembelian',
        'nama_barang',
        'jumlah_pembelian',
        'harga_beli',
    ];
}
