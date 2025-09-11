<?php

namespace App\Observers;

use App\Models\PembelianDetail;
use App\Models\Barang;
use Illuminate\Support\Facades\Log;

class PembelianDetailObserver
{
    /**
     * Handle the PembelianDetail "updating" event.
     * Dipanggil sebelum model diupdate
     */
    public function updating(PembelianDetail $pembelianDetail): void
    {
        // Simpan data lama untuk perbandingan
        $original = $pembelianDetail->getOriginal();
        
        // Jika jumlah_pembelian berubah, update field 'sisa' secara otomatis
        if ($pembelianDetail->isDirty('jumlah_pembelian')) {
            $oldJumlah = $original['jumlah_pembelian'] ?? 0;
            $newJumlah = $pembelianDetail->jumlah_pembelian;
            $oldSisa = $original['sisa'] ?? 0;
            
            // Hitung jumlah yang sudah terjual
            $jumlahTerjual = $oldJumlah - $oldSisa;
            
            // Update sisa dengan mempertimbangkan yang sudah terjual
            $pembelianDetail->sisa = $newJumlah - $jumlahTerjual;
            
            Log::info('PembelianDetail Observer: Auto-update sisa', [
                'id' => $pembelianDetail->id,
                'old_jumlah' => $oldJumlah,
                'new_jumlah' => $newJumlah,
                'jumlah_terjual' => $jumlahTerjual,
                'new_sisa' => $pembelianDetail->sisa
            ]);
        }
    }
    
    /**
     * Handle the PembelianDetail "updated" event.
     * Dipanggil setelah model diupdate
     */
    public function updated(PembelianDetail $pembelianDetail): void
    {
        // Jika jumlah_pembelian berubah, sinkronisasi stok barang
        if ($pembelianDetail->wasChanged('jumlah_pembelian')) {
            $this->resyncBarangStock($pembelianDetail->id_barang);
            
            Log::info('PembelianDetail Observer: Stok barang tersinkronisasi', [
                'id_barang' => $pembelianDetail->id_barang,
                'pembelian_detail_id' => $pembelianDetail->id
            ]);
        }
    }
    
    /**
     * Sinkronisasi ulang stok barang berdasarkan total pembelian dan penjualan
     */
    private function resyncBarangStock($idBarang): void
    {
        $barang = Barang::find($idBarang);
        if (!$barang) return;
        
        // Hitung total pembelian
        $totalPembelian = PembelianDetail::where('id_barang', $idBarang)
            ->sum('jumlah_pembelian');
        
        // Hitung total penjualan (jumlah_pembelian - sisa)
        $totalPenjualan = PembelianDetail::where('id_barang', $idBarang)
            ->selectRaw('SUM(jumlah_pembelian - sisa) as total_terjual')
            ->value('total_terjual') ?? 0;
        
        // Update stok barang
        $barang->stok = $totalPembelian - $totalPenjualan;
        $barang->save();
    }
}