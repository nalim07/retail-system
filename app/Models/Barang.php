<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';

    protected $fillable = [
        'nama_barang',
        'jenis_barang',
        'stok',
        'harga',
        'id_kategori'
    ];


    // Relasi ke KategoriBarang
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class, 'id_kategori');
    }

    // Relasi ke PembelianDetail
    public function penjualanDetails(): HasMany
    {
        return $this->hasMany(PenjualanDetail::class, 'id_barang');
    }
}
