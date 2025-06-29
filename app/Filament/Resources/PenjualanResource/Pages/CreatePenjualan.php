<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use Livewire\Form;
use Filament\Actions;
use App\Models\PembelianDetail;
use App\Models\PenjualanDetail;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PenjualanResource;

class CreatePenjualan extends CreateRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function afterCreate(): void
    {
        $penjualan = $this->record;

        // Proses setiap detail penjualan
        foreach ($penjualan->penjualanDetails as $detail) {
            $barang = $detail->barang;

            // Kurangi stok barang sesuai jumlah penjualan
            if ($barang) {
                $barang->stok -= $detail->jumlah_penjualan;
                $barang->save();
            }

            // Jika ada pembelian detail terkait, kurangi stok di pembelian detail
            // if ($detail->pembelianDetail) {
            //     $pembelianDetail = PembelianDetail::find($detail->pembelianDetail->id);
            //     if ($pembelianDetail) {
            //         $pembelianDetail->stok -= $detail->jumlah_penjualan;
            //         $pembelianDetail->save();
            //     }
            // }
        }
    }
}
