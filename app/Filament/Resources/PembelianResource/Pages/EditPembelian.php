<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Barang;
use App\Models\RiwayatPembelian;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class EditPembelian extends EditRecord
{
    protected static string $resource = PembelianResource::class;

    // Properti untuk menyimpan data lama
    protected $oldRecord;
    protected $oldPembelianDetails;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // PERBAIKAN: Hapus semua override form/schema, gunakan yang dari Resource

    protected function beforeSave(): void
    {
        // Simpan data lama untuk digunakan di afterSave
        $this->oldRecord = $this->record->replicate();
        $this->oldPembelianDetails = $this->record->pembelianDetails->map(function ($detail) {
            return [
                'id' => $detail->id,
                'id_barang' => $detail->id_barang,
                'jumlah_pembelian' => $detail->jumlah_pembelian,
                'satuan' => $detail->satuan,
                'harga_beli' => $detail->harga_beli,
                'harga_jual' => $detail->harga_jual,
            ];
        })->keyBy('id');
    }

    protected function afterSave(): void
    {
        $pembelian = $this->record;
        $oldPembelianDetails = $this->oldPembelianDetails;

        DB::transaction(function () use ($pembelian, $oldPembelianDetails) {
            // Proses detail yang dihapus - kurangi stok
            foreach ($oldPembelianDetails as $oldDetail) {
                $stillExists = $pembelian->pembelianDetails->contains('id', $oldDetail['id']);

                if (!$stillExists) {
                    // Detail dihapus, kurangi stok
                    $this->adjustStock($oldDetail['id_barang'], -$oldDetail['jumlah_pembelian']);

                    // Hapus riwayat pembelian terkait
                    $this->deleteRiwayatPembelian($oldDetail);
                }
            }

            // Proses detail yang ada atau baru
            foreach ($pembelian->pembelianDetails as $detail) {
                $oldDetail = $oldPembelianDetails[$detail->id] ?? null;

                // Jika ini adalah detail baru
                if (!$oldDetail) {
                    // Tambah stok untuk detail baru
                    $this->adjustStock($detail->id_barang, $detail->jumlah_pembelian);

                    // Buat riwayat pembelian baru
                    $this->createRiwayatPembelian($pembelian, $detail);
                    continue;
                }

                // Jika barang berubah
                if ($oldDetail['id_barang'] != $detail->id_barang) {
                    // Kurangi stok barang lama
                    $this->adjustStock($oldDetail['id_barang'], -$oldDetail['jumlah_pembelian']);

                    // Tambah stok barang baru
                    $this->adjustStock($detail->id_barang, $detail->jumlah_pembelian);

                    // Hapus riwayat lama dan buat yang baru
                    $this->deleteRiwayatPembelian($oldDetail);
                    $this->createRiwayatPembelian($pembelian, $detail);
                    continue;
                }

                // Jika jumlah berubah
                if ($oldDetail['jumlah_pembelian'] != $detail->jumlah_pembelian) {
                    // Hitung selisih dan sesuaikan stok
                    $selisih = $detail->jumlah_pembelian - $oldDetail['jumlah_pembelian'];
                    $this->adjustStock($detail->id_barang, $selisih);

                    // Update riwayat pembelian
                    $this->updateRiwayatPembelian($oldDetail, $detail);
                    continue;
                }

                // Jika harga atau satuan berubah tapi jumlah sama
                if (
                    $oldDetail['harga_beli'] != $detail->harga_beli ||
                    $oldDetail['satuan'] != $detail->satuan ||
                    $oldDetail['harga_jual'] != $detail->harga_jual
                ) {
                    // Update riwayat pembelian
                    $this->updateRiwayatPembelian($oldDetail, $detail);
                }
            }

            Notification::make()
                ->title('Pembelian berhasil diperbarui')
                ->success()
                ->send();
        });
    }

    // Fungsi helper untuk menyesuaikan stok barang
    private function adjustStock($idBarang, $jumlah): void
    {
        $barang = Barang::find($idBarang);
        if ($barang) {
            $barang->stok += $jumlah;
            $barang->save();
        }
    }

    // Fungsi helper untuk membuat riwayat pembelian baru
    private function createRiwayatPembelian($pembelian, $detail): void
    {
        $barang = Barang::find($detail->id_barang);
        if (!$barang) return;

        RiwayatPembelian::create([
            'tanggal_pembelian' => $pembelian->tgl_pembelian,
            'nama_barang' => $barang->nama_barang,
            'jumlah_pembelian' => $detail->jumlah_pembelian,
            'satuan' => $detail->satuan,
            'harga_beli' => $detail->harga_beli,
        ]);
    }

    // Fungsi helper untuk menghapus riwayat pembelian
    private function deleteRiwayatPembelian($detail): void
    {
        $barang = Barang::find($detail['id_barang']);
        if (!$barang) return;

        RiwayatPembelian::where('tanggal_pembelian', $this->oldRecord->tgl_pembelian)
            ->where('nama_barang', $barang->nama_barang)
            ->where('jumlah_pembelian', $detail['jumlah_pembelian'])
            ->delete();
    }

    // Fungsi helper untuk memperbarui riwayat pembelian
    private function updateRiwayatPembelian($oldDetail, $newDetail): void
    {
        $barang = Barang::find($newDetail->id_barang);
        if (!$barang) return;

        $riwayat = RiwayatPembelian::where('tanggal_pembelian', $this->oldRecord->tgl_pembelian)
            ->where('nama_barang', $barang->nama_barang)
            ->where('jumlah_pembelian', $oldDetail['jumlah_pembelian'])
            ->first();

        if ($riwayat) {
            $riwayat->update([
                'tanggal_pembelian' => $this->record->tgl_pembelian,
                'jumlah_pembelian' => $newDetail->jumlah_pembelian,
                'satuan' => $newDetail->satuan,
                'harga_beli' => $newDetail->harga_beli,
            ]);
        }
    }
}
