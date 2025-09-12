<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PembelianResource;
use Filament\Notifications\Notification;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    protected function beforeCreate(): void
    {
        $formState = $this->form->getState();
        $pembelianDetails = $formState['pembelianDetails'] ?? [];
        
        // Validasi barang duplikat
        $selectedBarang = [];
        foreach ($pembelianDetails as $detail) {
            $idBarang = $detail['id_barang'] ?? null;
            
            if ($idBarang) {
                if (in_array($idBarang, $selectedBarang)) {
                    Notification::make()
                        ->title('Barang Duplikat')
                        ->body('Tidak dapat menginput barang yang sama dalam satu pembelian.')
                        ->danger()
                        ->send();
                    
                    $this->halt(); // Hentikan proses create
                }
                
                $selectedBarang[] = $idBarang;
            }
        }
    }

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

            // Tidak perlu menyimpan ke riwayat pembelian lagi
        }
    }
}
