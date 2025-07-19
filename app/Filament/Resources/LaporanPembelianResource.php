<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Pembelian;
use Filament\Tables\Table;
use App\Models\RiwayatPembelian;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LaporanPembelianResource\Pages;
use App\Filament\Resources\LaporanPembelianResource\RelationManagers;

class LaporanPembelianResource extends Resource
{
    protected static ?string $model = RiwayatPembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Laporan Pembelian';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 2;
    protected static ?string $pluralLabel = 'Laporan Pembelian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no')->rowIndex(),
                Tables\Columns\TextColumn::make('tanggal_pembelian')
                    ->label('Tanggal Pembelian')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_barang')
                    ->label('Nama Barang')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jumlah_pembelian')
                    ->label('Total Barang')
                    ->sortable(),
                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Total Harga')
                    ->numeric()
                    ->prefix('Rp')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('tanggal')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('from')
                                ->label('Mulai Dari')
                                ->reactive(),
                            Forms\Components\DatePicker::make('to')
                                ->label('Sampai')
                                ->reactive(),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('tanggal_pembelian', '>=', $data['from']))
                            ->when($data['to'], fn($q) => $q->whereDate('tanggal_pembelian', '<=', $data['to']));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['from'] && !$data['to']) {
                            return null;
                        }

                        return 'Tanggal: ' .
                            ($data['from'] ? \Carbon\Carbon::parse($data['from'])->format('d/m/Y') : '-') .
                            ' s/d ' .
                            ($data['to'] ? \Carbon\Carbon::parse($data['to'])->format('d/m/Y') : '-');
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->modifyQueryUsing(function (Builder $query, HasTable $livewire) {
                $filterData = $livewire->tableFilters['tanggal'] ?? [];

                $query->when(
                    empty($filterData['from']) && empty($filterData['to']),
                    fn(Builder $q) => $q->whereRaw('1 = 0') // tidak tampilkan data jika filter kosong
                );
            })
            ->actions([
                // Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanPembelians::route('/'),
            // 'view' => Pages\ViewPembelian::route('/{record}/view'),
        ];
    }
}
