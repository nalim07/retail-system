<?php

namespace App\Filament\Resources\PembelianResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PembelianDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class FifoStokRelationManager extends RelationManager
{
    protected static string $relationship = 'pembelianDetails';

    protected static ?string $title = 'Monitoring FIFO - Semua Batch Barang';

    protected static ?string $label = 'Monitoring FIFO';

    protected static ?string $pluralLabel = 'Monitoring FIFO';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form tidak diperlukan karena ini hanya untuk menampilkan data
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('barang.nama_barang')
            ->content(function () {
                // Ambil SEMUA data pembelian detail dengan stok > 0 dari seluruh sistem
                $records = PembelianDetail::query()
                    ->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian')
                    ->join('barang', 'barang.id', '=', 'pembelian_detail.id_barang')
                    ->with(['barang', 'pembelian'])
                    ->select('pembelian_detail.*')
                    ->where('pembelian_detail.sisa', '>', 0)
                    ->orderBy('barang.nama_barang', 'asc')
                    ->orderBy('pembelian.tgl_pembelian', 'asc')
                    ->get();

                return view('filament.resources.pembelian-resource.relation-managers.fifo-boxes', [
                    'records' => $records
                ]);
            })
            ->query(function () {
                // Override query untuk menampilkan semua data, bukan hanya yang terkait dengan pembelian tertentu
                return PembelianDetail::query()
                    ->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian')
                    ->join('barang', 'barang.id', '=', 'pembelian_detail.id_barang')
                    ->with(['barang', 'pembelian'])
                    ->select('pembelian_detail.*')
                    ->where('pembelian_detail.sisa', '>', 0)
                    ->orderBy('barang.nama_barang', 'asc')
                    ->orderBy('pembelian.tgl_pembelian', 'asc');
            })
            ->filters([
                Tables\Filters\SelectFilter::make('barang')
                    ->label('Filter Barang')
                    ->relationship('barang', 'nama_barang')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status_stok')
                    ->label('Status Stok')
                    ->options([
                        'tersedia' => 'Masih Tersedia',
                        'habis' => 'Habis',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] === 'tersedia',
                            fn (Builder $query): Builder => $query->where('sisa', '>', 0),
                        )->when(
                            $data['value'] === 'habis',
                            fn (Builder $query): Builder => $query->where('sisa', '=', 0),
                        );
                    }),
            ])
            ->emptyStateHeading('Tidak Ada Data Stok FIFO')
            ->emptyStateDescription('Belum ada data pembelian untuk ditampilkan dalam sistem FIFO.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
