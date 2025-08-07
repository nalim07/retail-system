<?php

namespace App\Filament\Actions;

use App\Models\Barang;
use App\Models\PembelianDetail;
use App\Models\RiwayatPenjualan;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class DeletePenjualanBulkAction extends DeleteBulkAction
{
    // Properti untuk menyimpan data penjualan
    protected array $penjualanData = [];
    
    protected function setUp(): void
    {
        parent::setUp();

        // Menggunakan action untuk menangani logika delete bulk
        $this->action(function (Collection $records): void {
            // Simpan data penjualan sebelum dihapus
            $this->penjualanData = [];

            foreach ($records as $record) {
                $details = $record->penjualanDetails->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'id_barang' => $detail->id_barang,
                        'jumlah_penjualan' => $detail->jumlah_penjualan,
                        'id_pembelian_detail' => $detail->id_pembelian_detail,
                    ];
                })->toArray();

                $this->penjualanData[] = [
                    'tgl_penjualan' => $record->tgl_penjualan,
                    'nama_pelanggan' => $record->pelanggan ? $record->pelanggan->nama_pelanggan : '-',
                    'details' => $details,
                ];
            }
            
            // Hapus records
            $records->each(fn ($record) => $record->delete());
            
            // Proses setelah penghapusan berhasil
            DB::transaction(function () {
                $restoredCount = 0;

                foreach ($this->penjualanData as $penjualan) {
                    // Kembalikan stok untuk setiap detail penjualan
                    foreach ($penjualan['details'] as $detail) {
                        $this->restoreStock(
                            $detail['id_barang'],
                            $detail['jumlah_penjualan'],
                            $detail['id_pembelian_detail']
                        );
                        $restoredCount++;
                    }

                    // Hapus riwayat penjualan terkait
                    RiwayatPenjualan::where('tanggal_penjualan', $penjualan['tgl_penjualan'])
                        ->where(function ($query) use ($penjualan) {
                            $query->whereIn('nama_barang', collect($penjualan['details'])->map(function ($detail) {
                                $barang = Barang::find($detail['id_barang']);
                                return $barang ? $barang->nama_barang : null;
                            }))
                            ->orWhere('nama_pelanggan', $penjualan['nama_pelanggan']);
                        })
                        ->delete();
                }

                Notification::make()
                    ->title('Penjualan berhasil dihapus')
                    ->body("Stok untuk {$restoredCount} item telah dikembalikan.")
                    ->success()
                    ->send();
            });
            
            $this->success();
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
}