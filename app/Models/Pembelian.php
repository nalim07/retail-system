<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelian';

    protected $fillable = [
        'tgl_pembelian',
    ];

    // Casting attributes
    protected $casts = [
        'tgl_pembelian' => 'datetime',
    ];

    // Relasi ke PembelianDetail
    public function pembelianDetails()
    {
        return $this->hasMany(PembelianDetail::class, 'id_pembelian');
    }

    public function getJumlahTotalAttribute()
    {
        return $this->pembelianDetails->sum('jumlah_pembelian');
    }
}
