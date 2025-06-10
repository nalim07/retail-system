<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'penjualan';

    protected $fillable = [
        'tgl_penjualan',
        'id_pelanggan'
    ];

    protected $casts = [
        'tgl_penjualan' => 'datetime',
    ];

    // Relasi ke Pelanggan
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan');
    }

    public function getJumlahTotalAttribute()
    {
        return $this->penjualanDetails->sum('jumlah_penjualan');
    }


    // Relasi ke Detail Penjualan
    public function penjualanDetails(): HasMany
    {
        return $this->hasMany(PenjualanDetail::class, 'id_penjualan');
    }
}
