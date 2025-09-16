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
            ->columns([
                Tables\Columns\TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pembelian.tgl_pembelian')
                    ->label('Tanggal Pembelian')
                    ->date('d M Y')
                    ->sortable()
                    ->description('Tanggal batch pembelian'),

                Tables\Columns\TextColumn::make('jumlah_pembelian')
                    ->label('Jumlah Pembelian')
                    ->numeric()
                    ->suffix(fn($record) => ' ' . $record->satuan)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sisa')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->suffix(fn($record) => ' ' . $record->satuan)
                    ->alignCenter()
                    ->color(fn($record) => match (true) {
                        $record->sisa == 0 => 'danger',
                        $record->sisa <= ($record->jumlah_pembelian * 0.2) => 'warning',
                        default => 'success',
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->numeric()
                    ->sortable()
                    ->prefix('Rp')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('status_fifo')
                    ->label('Status FIFO')
                    ->badge()
                    ->state(function ($record) {
                        if ($record->sisa == 0) {
                            return 'Habis';
                        }

                        // Cek apakah ini batch yang akan keluar pertama untuk barang ini
                        $earliestBatch = PembelianDetail::where('id_barang', $record->id_barang)
                            ->where('sisa', '>', 0)
                            ->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian')
                            ->orderBy('pembelian.tgl_pembelian')
                            ->select('pembelian_detail.id')
                            ->first();

                        if ($earliestBatch && $earliestBatch->id == $record->id) {
                            return 'Keluar Pertama';
                        }

                        return 'Menunggu';
                    })
                    ->color(fn($state) => match ($state) {
                        'Keluar Pertama' => 'success',
                        'Menunggu' => 'warning',
                        'Habis' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('urutan_fifo')
                    ->label('Urutan FIFO')
                    ->alignCenter()
                    ->state(function ($record) {
                        if ($record->sisa == 0) {
                            return '-';
                        }

                        // Hitung urutan batch ini dalam antrian FIFO untuk barang yang sama
                        $urutan = PembelianDetail::where('id_barang', $record->id_barang)
                            ->where('sisa', '>', 0)
                            ->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian')
                            ->where('pembelian.tgl_pembelian', '<', $record->pembelian->tgl_pembelian)
                            ->count() + 1;

                        return $urutan;
                    })
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state === '-' => 'gray',
                        $state == 1 => 'success',
                        $state <= 3 => 'warning',
                        default => 'gray',
                    }),
            ])
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
            ->headerActions([
                // Tidak ada action untuk create karena ini hanya untuk display
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->modalHeading('Detail Batch Pembelian FIFO')
                    ->modalContent(function ($record) {
                        return view('filament.resources.pembelian-resource.relation-managers.fifo-detail', [
                            'record' => $record
                        ]);
                    }),
            ])
            ->bulkActions([
                // Tidak ada bulk actions
            ])
            ->defaultSort('pembelian.tgl_pembelian', 'asc')
            ->modifyQueryUsing(function (Builder $query) {
                // Ambil semua batch dari semua pembelian, bukan hanya dari pembelian yang sedang dilihat
                return PembelianDetail::query()
                    ->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian')
                    ->join('barang', 'barang.id', '=', 'pembelian_detail.id_barang')
                    ->with(['barang', 'pembelian'])
                    ->select('pembelian_detail.*')
                    ->where('pembelian_detail.sisa', '>', 0) // Hanya tampilkan yang masih ada stoknya
                    ->orderBy('pembelian.tgl_pembelian', 'asc');
            })
            ->emptyStateHeading('Tidak Ada Data Stok FIFO')
            ->emptyStateDescription('Belum ada data pembelian untuk ditampilkan dalam sistem FIFO.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
