<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use Filament\Actions;
use App\Models\RiwayatPembelian;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PembelianResource;

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

            // Simpan ke riwayat pembelian
            RiwayatPembelian::create([
                'tanggal_pembelian' => $pembelian->tgl_pembelian,
                'nama_barang' => $barang->nama_barang,
                'jumlah_pembelian' => $detail->jumlah_pembelian,
                'harga_beli' => $detail->harga_beli,
            ]);
        }
    }
}
