<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\KategoriBarang;
use App\Models\PembelianDetail;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\LaporanStokResource\Pages;

class LaporanStokResource extends Resource
{
    protected static ?string $model = PembelianDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Stok';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no')->rowIndex(),

                Tables\Columns\TextColumn::make('pembelian.tgl_pembelian')
                    ->label('Tanggal Pembelian')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('barang.jenis_barang')
                    ->label('Jenis Barang')
                    ->sortable(),

                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->numeric()
                    ->prefix('Rp'),

                Tables\Columns\TextColumn::make('jumlah_pembelian')
                    ->label('Jumlah Awal')
                    ->numeric(),

                Tables\Columns\TextColumn::make('sisa')
                    ->label('Sisa Stok')
                    ->numeric(),

                Tables\Columns\TextColumn::make('barang.kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable(),
            ])
            ->filters([
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari')->reactive(),
                        DatePicker::make('to')->label('Sampai')->reactive(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('pembelian.tgl_pembelian', '>=', $data['from']))
                            ->when($data['to'], fn($q) => $q->whereDate('pembelian.tgl_pembelian', '<=', $data['to']));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['from'] && !$data['to']) {
                            return null;
                        }

                        return 'Tanggal: ' . ($data['from'] ?? '-') . ' s/d ' . ($data['to'] ?? '-');
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->modifyQueryUsing(function (Builder $query) {
                // Pastikan join pembelian agar filter by tanggal bisa
                $query->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian');

                // Jika belum pilih tanggal, jangan tampilkan data
                $filters = request()->input('tableFilters.tanggal', []);
                if (empty($filters['from']) && empty($filters['to'])) {
                    $query->whereRaw('1 = 0'); // kosongkan hasil
                }
            })

            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanStoks::route('/'),
        ];
    }
}
