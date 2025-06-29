<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    protected function afterCreate(): void
    {
        $pembelian = $this->record;

        foreach ($pembelian->pembelianDetails as $detail) {
            $barang = $detail->barang;

            // Tambah stok total ke table barang
            $barang->stok += $detail->jumlah_pembelian;
            $barang->save();
        }
    }
}
