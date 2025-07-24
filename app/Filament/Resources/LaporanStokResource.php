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
use Filament\Tables\Contracts\HasTable;

class LaporanStokResource extends Resource
{
    protected static ?string $model = PembelianDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Stok';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 1;
    protected static ?string $pluralLabel = 'Laporan Stok';

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
                    ->label('Stok Awal')
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
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('from')
                                    ->label('Mulai Dari')
                                    ->reactive(),
                                Forms\Components\DatePicker::make('to')
                                    ->label('Sampai')
                                    ->reactive(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereHas('pembelian', fn($sub) => $sub->whereDate('tgl_pembelian', '>=', $data['from'])))
                            ->when($data['to'], fn($q) => $q->whereHas('pembelian', fn($sub) => $sub->whereDate('tgl_pembelian', '<=', $data['to'])));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['from'] && !$data['to']) {
                            return null;
                        }

                        return 'Tanggal: ' . ($data['from'] ? \Carbon\Carbon::parse($data['from'])->format('d/m/Y') : '-') . ' s/d ' . ($data['to'] ? \Carbon\Carbon::parse($data['to'])->format('d/m/Y') : '-');
                    }),
                SelectFilter::make('kategori')
                    ->relationship('barang.kategori', 'nama_kategori')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Kategori Barang'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->modifyQueryUsing(function (Builder $query, HasTable $livewire) {
                // Ambil data filter langsung dari state komponen Livewire, bukan dari request()
                $filterData = $livewire->tableFilters['tanggal'] ?? [];
                $query->when(
                    empty($filterData['from']) && empty($filterData['to']),
                    fn(Builder $q) => $q->whereRaw('1 = 0')
                );
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
