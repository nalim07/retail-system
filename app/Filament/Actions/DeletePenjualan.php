<?php

namespace App\Filament\Actions;

use App\Models\Barang;
use App\Models\PembelianDetail;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class DeletePenjualan extends DeleteAction
{
    // Properti untuk menyimpan data penjualan
    protected array $penjualanDetails = [];
    protected $tglPenjualan;
    protected string $namaPelanggan = '-';
    
    public static function getDefaultName(): ?string
    {
        return 'delete';
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Menggunakan process untuk menangani logika delete
        $this->action(function (Model $record): void {
            // Simpan data penjualan sebelum dihapus
            $this->penjualanDetails = $record->penjualanDetails->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'id_barang' => $detail->id_barang,
                    'jumlah_penjualan' => $detail->jumlah_penjualan,
                    'id_pembelian_detail' => $detail->id_pembelian_detail,
                ];
            })->toArray();

            $this->tglPenjualan = $record->tgl_penjualan;
            $this->namaPelanggan = $record->pelanggan ? $record->pelanggan->nama_pelanggan : '-';
            
            // Hapus record
            $result = $record->delete();
            
            if (!$result) {
                $this->failure();
                return;
            }
            
            // Proses setelah penghapusan berhasil
            DB::transaction(function () {
                // Kembalikan stok untuk setiap detail penjualan
                foreach ($this->penjualanDetails as $detail) {
                    $this->restoreStock(
                        $detail['id_barang'],
                        $detail['jumlah_penjualan'],
                        $detail['id_pembelian_detail']
                    );
                }

                // Tidak perlu menghapus riwayat penjualan terkait lagi

                Notification::make()
                    ->title('Penjualan berhasil dihapus')
                    ->body('Stok barang telah dikembalikan.')
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