<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = [
        'nama_barang',
        'jenis_barang',
        'stok',
        'satuan',
        'harga_jual',
        'id_kategori'
    ];


    // Relasi ke KategoriBarang
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class, 'id_kategori');
    }

    // Relasi ke PembelianDetail
    public function pembelianDetails(): HasMany
    {
        return $this->hasMany(PembelianDetail::class, 'id_barang');
    }

    // Relasi ke PenjualanDetail
    public function penjualanDetails(): HasMany
    {
        return $this->hasMany(PenjualanDetail::class, 'id_barang');
    }
}
