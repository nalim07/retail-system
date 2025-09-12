<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use Filament\Actions;
use App\Models\Barang;
use App\Models\PembelianDetail;
use App\Models\PenjualanDetail;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PenjualanResource;

class CreatePenjualan extends CreateRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function beforeCreate(): void
    {
        $formState = $this->form->getState();
        $penjualanDetails = $formState['penjualanDetails'] ?? [];

        foreach ($penjualanDetails as $detail) {
            $jumlah = $detail['jumlah_penjualan'] ?? 0;
            $idBarang = $detail['id_barang'] ?? null;

            if (!$idBarang || $jumlah <= 0) {
                continue;
            }

            $totalSisa = PembelianDetail::where('id_barang', $idBarang)
                ->where('sisa', '>', 0)
                ->sum('sisa');

            if ($jumlah > $totalSisa) {
                Notification::make()
                    ->title('Stok Tidak Cukup')
                    ->body("Barang ID {$idBarang} hanya tersedia {$totalSisa} unit, diminta {$jumlah} unit.")
                    ->danger()
                    ->send();

                $this->halt(); // Hentikan proses create
            }
        }
    }

    protected function afterCreate(): void
    {
        $penjualan = $this->record;

        DB::transaction(function () use ($penjualan) {
            foreach ($penjualan->penjualanDetails as $detail) {
                $jumlah = $detail->jumlah_penjualan;
                $barang = $detail->barang;

                if (!$barang) {
                    Notification::make()
                        ->title('Barang tidak ditemukan')
                        ->body("Barang dengan ID {$detail->id_barang} tidak ditemukan.")
                        ->danger()
                        ->send();

                    continue;
                }

                // FIFO - ambil batch pembelian paling awal
                $batchList = PembelianDetail::where('id_barang', $barang->id)
                    ->where('sisa', '>', 0)
                    ->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian')
                    ->orderBy('pembelian.tgl_pembelian')
                    ->select('pembelian_detail.*')
                    ->get();

                foreach ($batchList as $batch) {
                    if ($jumlah <= 0) break;

                    $dipakai = min($jumlah, $batch->sisa);

                    $batch->sisa -= $dipakai;
                    $batch->save();

                    $barang->stok -= $dipakai;
                    $barang->save();

                    // Update detail (asumsinya hanya satu record per barang, jadi bisa update)
                    $detail->update([
                        'id_pembelian_detail' => $batch->id,
                        'jumlah_penjualan' => $dipakai,
                    ]);

                    $jumlah -= $dipakai;
                }

                if ($jumlah > 0) {
                    Notification::make()
                        ->title('Gagal memproses stok')
                        ->body("Stok barang '{$barang->nama_barang}' tidak mencukupi.")
                        ->danger()
                        ->send();
                }

                // Tidak perlu menyimpan ke riwayat lagi
            }
        });
    }
}
