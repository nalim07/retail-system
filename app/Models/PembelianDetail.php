<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class PembelianDetail extends Model
{
    use HasFactory;

    protected $table = 'pembelian_detail';

    protected $fillable = [
        'id_pembelian',
        'id_barang',
        'jumlah_pembelian',
        'sisa',
        'satuan',
        'harga_beli'
    ];

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'id_pembelian');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function penjualanDetails()
    {
        return $this->hasMany(PenjualanDetail::class, 'id_pembelian_detail');
    }

    protected static function booted()
    {
        static::creating(function ($detail) {
            $detail->sisa = $detail->jumlah_pembelian;
        });
    }

    /**
     * Hitung berapa banyak yang sudah terjual dari batch pembelian ini
     */
    public function calculateSoldQuantity()
    {
        return \App\Models\PenjualanDetail::where('id_pembelian_detail', $this->id)
            ->sum('jumlah_penjualan');
    }

    /**
     * Update field 'sisa' berdasarkan perhitungan yang benar
     */
    public function updateSisa()
    {
        $jumlahTerjual = $this->calculateSoldQuantity();
        $this->sisa = $this->jumlah_pembelian - $jumlahTerjual;
        $this->save();
        
        return $this;
    }
}
