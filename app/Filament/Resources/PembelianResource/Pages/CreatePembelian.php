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
        foreach ($this->record->pembelianDetails as $detail) {
            $barang = $detail->barang;
            $barang->stok += $detail->jumlah_pembelian;
            $barang->save();

            // Optional: set sisa untuk FIFO
            $detail->sisa = $detail->jumlah_pembelian;
            $detail->save();
        }
    }
}
