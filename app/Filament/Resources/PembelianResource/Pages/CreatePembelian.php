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

            // Update atau isi satuan jika kosong atau berubah
            if (empty($barang->satuan)) {
                // Jika satuan kosong, isi dari pembelian_detail
                $barang->satuan = $detail->satuan;
            } elseif ($barang->satuan !== $detail->satuan) {
                // Jika satuan berbeda, update (opsional: bisa dikonfirmasi user atau log peringatan)
                $barang->satuan = $detail->satuan;
            }

            $barang->save();

            // Simpan ke riwayat pembelian
            RiwayatPembelian::create([
                'tanggal_pembelian' => $pembelian->tgl_pembelian,
                'nama_barang' => $barang->nama_barang,
                'jumlah_pembelian' => $detail->jumlah_pembelian,
                'satuan' => $detail->satuan,
                'harga_beli' => $detail->harga_beli,
            ]);
        }
    }
}
