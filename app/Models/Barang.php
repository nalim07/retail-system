<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = [
        'nama_barang',
        'kategori_id',
        'harga',
        'stok',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriBarang::class, 'kategori_id');
    }
}
