<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Barang;
use App\Models\KategoriBarang;
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
    protected static ?string $model = Barang::class;

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

                Tables\Columns\TextColumn::make('nama_barang')
                    ->label('Nama Barang')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jenis_barang')
                    ->label('Jenis Barang')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok Tersedia')
                    ->numeric(),

                Tables\Columns\TextColumn::make('satuan')
                    ->label('Satuan'),

                Tables\Columns\TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->numeric()
                    ->prefix('Rp'),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->relationship('kategori', 'nama_kategori')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Kategori Barang'),

                SelectFilter::make('jenis_barang')
                    ->options([
                        'Makanan' => 'Makanan',
                        'Minuman' => 'Minuman',
                        'Elektronik' => 'Elektronik',
                        'Pakaian' => 'Pakaian',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->label('Jenis Barang'),

                // Filter::make('stok_tersedia')
                //     ->query(fn (Builder $query): Builder => $query->where('stok', '>', 0))
                //     ->label('Stok Tersedia')
                //     ->toggle(),

                Filter::make('stok_kosong')
                    ->query(fn (Builder $query): Builder => $query->where('stok', '=', 0))
                    ->label('Stok Kosong')
                    ->toggle(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->persistFiltersInSession()
            ->modifyQueryUsing(function (Builder $query, HasTable $livewire) {
                // Tampilkan semua data barang tanpa filter default
                return $query;
            })

            ->actions([])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
