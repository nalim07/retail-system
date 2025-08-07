<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use Filament\Actions;
use App\Models\Barang;
use App\Models\PembelianDetail;
use App\Models\PenjualanDetail;
use App\Models\RiwayatPenjualan;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PenjualanResource;
use App\Filament\Actions\DeletePenjualan;

class EditPenjualan extends EditRecord
{
    protected static string $resource = PenjualanResource::class;
    
    // Properti untuk menyimpan data lama
    protected $oldRecord;
    protected $oldPenjualanDetails;

    protected function getHeaderActions(): array
    {
        return [
            // Header actions kosong karena DeletePenjualan sudah ditambahkan di table actions
        ];
    }

    protected function beforeSave(): void
    {
        // Simpan data lama untuk digunakan di afterSave
        $this->oldRecord = $this->record->replicate();
        $this->oldPenjualanDetails = $this->record->penjualanDetails->map(function ($detail) {
            return [
                'id' => $detail->id,
                'id_barang' => $detail->id_barang,
                'jumlah_penjualan' => $detail->jumlah_penjualan,
                'id_pembelian_detail' => $detail->id_pembelian_detail,
            ];
        })->keyBy('id');

        // Validasi stok untuk item baru atau yang diubah
        $formState = $this->form->getState();
        $penjualanDetails = $formState['penjualanDetails'] ?? [];

        foreach ($penjualanDetails as $detail) {
            // Skip jika tidak ada ID barang atau jumlah
            if (empty($detail['id_barang']) || empty($detail['jumlah_penjualan'])) {
                continue;
            }

            $idBarang = $detail['id_barang'];
            $jumlah = $detail['jumlah_penjualan'];
            $detailId = $detail['id'] ?? null;

            // Jika ini adalah detail yang sudah ada
            if ($detailId && isset($this->oldPenjualanDetails[$detailId])) {
                $oldDetail = $this->oldPenjualanDetails[$detailId];
                
                // Jika barang sama dan jumlah tidak berubah, skip validasi
                if ($oldDetail['id_barang'] == $idBarang && $oldDetail['jumlah_penjualan'] == $jumlah) {
                    continue;
                }

                // Jika barang sama tapi jumlah berubah, hitung selisih
                if ($oldDetail['id_barang'] == $idBarang) {
                    // Hanya validasi jika jumlah baru lebih besar dari jumlah lama
                    if ($jumlah <= $oldDetail['jumlah_penjualan']) {
                        continue;
                    }
                    
                    // Hitung selisih yang perlu divalidasi
                    $jumlahValidasi = $jumlah - $oldDetail['jumlah_penjualan'];
                } else {
                    // Barang berbeda, validasi jumlah penuh
                    $jumlahValidasi = $jumlah;
                }
            } else {
                // Detail baru, validasi jumlah penuh
                $jumlahValidasi = $jumlah;
            }

            // Validasi stok
            $totalSisa = PembelianDetail::where('id_barang', $idBarang)
                ->where('sisa', '>', 0)
                ->sum('sisa');

            if ($jumlahValidasi > $totalSisa) {
                Notification::make()
                    ->title('Stok Tidak Cukup')
                    ->body("Barang ID {$idBarang} hanya tersedia {$totalSisa} unit tambahan, diminta {$jumlahValidasi} unit.")
                    ->danger()
                    ->send();

                $this->halt(); // Hentikan proses edit
            }
        }
    }

    protected function afterSave(): void
    {
        $penjualan = $this->record;
        $oldPenjualanDetails = $this->oldPenjualanDetails;

        DB::transaction(function () use ($penjualan, $oldPenjualanDetails) {
            // Proses detail yang dihapus - kembalikan stok
            foreach ($oldPenjualanDetails as $oldDetail) {
                $stillExists = $penjualan->penjualanDetails->contains('id', $oldDetail['id']);
                
                if (!$stillExists) {
                    // Detail dihapus, kembalikan stok
                    $this->restoreStock($oldDetail['id_barang'], $oldDetail['jumlah_penjualan'], $oldDetail['id_pembelian_detail']);
                }
            }

            // Proses detail yang ada atau baru
            foreach ($penjualan->penjualanDetails as $detail) {
                $oldDetail = $oldPenjualanDetails[$detail->id] ?? null;
                
                // Jika ini adalah detail baru atau barang berubah
                if (!$oldDetail || $oldDetail['id_barang'] != $detail->id_barang) {
                    // Proses seperti pembuatan baru
                    $this->processNewDetail($penjualan, $detail);
                    continue;
                }
                
                // Jika jumlah berubah
                if ($oldDetail['jumlah_penjualan'] != $detail->jumlah_penjualan) {
                    // Kembalikan stok lama
                    $this->restoreStock($detail->id_barang, $oldDetail['jumlah_penjualan'], $oldDetail['id_pembelian_detail']);
                    
                    // Proses seperti pembuatan baru
                    $this->processNewDetail($penjualan, $detail);
                }
            }

            // Hapus riwayat penjualan lama dan buat yang baru
            RiwayatPenjualan::where('tanggal_penjualan', $this->oldRecord->tgl_penjualan)
                ->where(function ($query) use ($penjualan) {
                    $query->whereIn('nama_barang', $penjualan->penjualanDetails->map(function ($detail) {
                        return $detail->barang ? $detail->barang->nama_barang : null;
                    }))
                    ->orWhere('nama_pelanggan', $this->oldRecord->pelanggan ? $this->oldRecord->pelanggan->nama_pelanggan : '-');
                })
                ->delete();

            // Buat riwayat baru untuk semua detail
            foreach ($penjualan->penjualanDetails as $detail) {
                $barang = $detail->barang;
                if (!$barang) continue;

                $namaPelanggan = $penjualan->pelanggan ? $penjualan->pelanggan->nama_pelanggan : '-';

                RiwayatPenjualan::create([
                    'tanggal_penjualan' => $penjualan->tgl_penjualan ?? now(),
                    'nama_barang' => $barang->nama_barang,
                    'jumlah_penjualan' => $detail->jumlah_penjualan,
                    'harga_jual' => $detail->harga_jual,
                    'satuan' => $barang->satuan,
                    'nama_pelanggan' => $namaPelanggan,
                ]);
            }
        });
    }

    // Mengembalikan stok barang
    private function restoreStock($idBarang, $jumlah, $idPembelianDetail): void
    {
        // Kembalikan stok ke pembelian detail
        $pembelianDetail = PembelianDetail::find($idPembelianDetail);
        if ($pembelianDetail) {
            $pembelianDetail->sisa += $jumlah;
            $pembelianDetail->save();
        }

        // Kembalikan stok barang
        $barang = Barang::find($idBarang);
        if ($barang) {
            $barang->stok += $jumlah;
            $barang->save();
        }
    }

    // Proses detail baru atau yang diubah
    private function processNewDetail($penjualan, $detail): void
    {
        $jumlah = $detail->jumlah_penjualan;
        $barang = $detail->barang;

        if (!$barang) {
            Notification::make()
                ->title('Barang tidak ditemukan')
                ->body("Barang dengan ID {$detail->id_barang} tidak ditemukan.")
                ->danger()
                ->send();

            return;
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

            // Update detail
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
    }
}
