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
        // Validasi barang duplikat
        $formState = $this->form->getState();
        $pembelianDetails = $formState['pembelianDetails'] ?? [];
        
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
                    
                    $this->halt(); // Hentikan proses edit
                }
                
                $selectedBarang[] = $idBarang;
            }
        }
        
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
        // Log untuk debugging
        \Log::info('EditPembelian afterSave called', [
            'pembelian_id' => $this->record->id,
            'old_details_count' => count($this->oldPembelianDetails),
            'new_details_count' => $this->record->pembelianDetails->count()
        ]);
        
        $pembelian = $this->record;
        $oldPembelianDetails = $this->oldPembelianDetails;

        DB::transaction(function () use ($pembelian, $oldPembelianDetails) {
            // Proses detail yang dihapus - kurangi stok
            foreach ($oldPembelianDetails as $oldDetail) {
                $stillExists = $pembelian->pembelianDetails->contains('id', $oldDetail['id']);

                if (!$stillExists) {
                    // Cek apakah batch yang akan dihapus sudah ada penjualan
                    $jumlahTerjual = $this->calculateSoldQuantity($oldDetail['id']);
                    
                    if ($jumlahTerjual > 0) {
                        Notification::make()
                            ->title('Tidak dapat menghapus detail pembelian')
                            ->body("Dari batch ini sudah terjual {$jumlahTerjual} unit. Detail pembelian tidak dapat dihapus setelah ada penjualan.")
                            ->danger()
                            ->send();
                        
                        $this->halt(); // Hentikan proses edit
                    }
                    
                    // Detail dihapus, kurangi stok
                    $this->adjustStock($oldDetail['id_barang'], -$oldDetail['jumlah_pembelian']);
                    
                    // Sinkronisasi ulang stok barang
                    $this->resyncBarangStock($oldDetail['id_barang']);
                    
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

                    // Set field 'sisa' untuk detail baru (belum ada penjualan)
                    $detail->sisa = $detail->jumlah_pembelian;
                    $detail->save();

                    // Sinkronisasi ulang stok barang
                    $this->resyncBarangStock($detail->id_barang);

                    // Buat riwayat pembelian baru
                    $this->createRiwayatPembelian($pembelian, $detail);
                    continue;
                }

                // Jika barang berubah
                if ($oldDetail['id_barang'] != $detail->id_barang) {
                    // Cek apakah batch lama sudah ada penjualan
                    $jumlahTerjual = $this->calculateSoldQuantity($detail->id);
                    
                    if ($jumlahTerjual > 0) {
                        Notification::make()
                            ->title('Tidak dapat mengubah barang')
                            ->body("Dari batch ini sudah terjual {$jumlahTerjual} unit. Barang tidak dapat diubah setelah ada penjualan.")
                            ->danger()
                            ->send();
                        
                        $this->halt(); // Hentikan proses edit
                    }
                    
                    // Kurangi stok barang lama
                    $this->adjustStock($oldDetail['id_barang'], -$oldDetail['jumlah_pembelian']);

                    // Tambah stok barang baru
                    $this->adjustStock($detail->id_barang, $detail->jumlah_pembelian);

                    // Set field 'sisa' untuk barang baru
                    $detail->sisa = $detail->jumlah_pembelian;
                    $detail->save();

                    // Sinkronisasi ulang stok untuk kedua barang
                    $this->resyncBarangStock($oldDetail['id_barang']); // Barang lama
                    $this->resyncBarangStock($detail->id_barang); // Barang baru

                    // Hapus riwayat lama dan buat yang baru
                    $this->deleteRiwayatPembelian($oldDetail);
                    $this->createRiwayatPembelian($pembelian, $detail);
                    continue;
                }

                // Jika jumlah berubah
                if ($oldDetail['jumlah_pembelian'] != $detail->jumlah_pembelian) {
                    // Hitung berapa banyak yang sudah terjual dari batch ini
                    $jumlahTerjual = $this->calculateSoldQuantity($detail->id);
                    
                    // Validasi: jumlah baru tidak boleh kurang dari yang sudah terjual
                    if ($detail->jumlah_pembelian < $jumlahTerjual) {
                        Notification::make()
                            ->title('Tidak dapat mengurangi jumlah pembelian')
                            ->body("Dari batch ini sudah terjual {$jumlahTerjual} unit. Jumlah pembelian tidak boleh kurang dari yang sudah terjual.")
                            ->danger()
                            ->send();
                        
                        $this->halt(); // Hentikan proses edit
                    }
                    
                    // Hitung selisih dan sesuaikan stok
                    $selisih = $detail->jumlah_pembelian - $oldDetail['jumlah_pembelian'];
                    $this->adjustStock($detail->id_barang, $selisih);

                    // Update field 'sisa' dengan mempertimbangkan yang sudah terjual
                    $detail->sisa = $detail->jumlah_pembelian - $jumlahTerjual;
                    $detail->save();

                    // Sinkronisasi ulang stok barang
                    $this->resyncBarangStock($detail->id_barang);

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
                    
                    // Tetap sinkronisasi untuk memastikan konsistensi
                    $this->resyncBarangStock($detail->id_barang);
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

    /**
     * Hitung berapa banyak yang sudah terjual dari batch pembelian tertentu
     */
    private function calculateSoldQuantity($pembelianDetailId)
    {
        return \App\Models\PenjualanDetail::where('id_pembelian_detail', $pembelianDetailId)
            ->sum('jumlah_penjualan');
    }

    /**
     * Sinkronisasi ulang stok barang berdasarkan semua pembelian dan penjualan
     */
    private function resyncBarangStock($idBarang)
    {
        // Hitung total pembelian
        $totalPembelian = \App\Models\PembelianDetail::where('id_barang', $idBarang)
            ->sum('jumlah_pembelian');
        
        // Hitung total penjualan
        $totalPenjualan = \App\Models\PenjualanDetail::whereHas('pembelianDetail', function($query) use ($idBarang) {
            $query->where('id_barang', $idBarang);
        })->sum('jumlah_penjualan');
        
        // Update stok barang
        $stokAktual = $totalPembelian - $totalPenjualan;
        \App\Models\Barang::where('id', $idBarang)
            ->update(['stok' => $stokAktual]);
    }
}
