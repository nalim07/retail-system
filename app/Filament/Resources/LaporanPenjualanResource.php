<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Penjualan;
use Filament\Tables\Table;
use App\Models\LaporanPenjualan;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LaporanPenjualanResource\Pages;
use App\Filament\Resources\LaporanPenjualanResource\RelationManagers;

class LaporanPenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Penjualan';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 3;
    protected static ?string $pluralLabel = 'Laporan Penjualan';

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
                Tables\Columns\TextColumn::make('tgl_penjualan')
                    ->label('Tanggal Penjualan')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('penjualanDetails.jumlah_penjualan')
                    ->label('Total Barang')
                    ->formatStateUsing(function ($state, $record) {
                        $details = [];
                        foreach ($record->penjualanDetails as $detail) {
                            $details[] = $detail->jumlah_penjualan . ' ' . $detail->barang->nama_barang;
                        }
                        return implode(', ', $details);
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('penjualanDetails.barang.nama_barang')
                    ->label('Nama Barang')
                    ->formatStateUsing(function ($state, $record) {
                        $details = [];
                        foreach ($record->penjualanDetails as $detail) {
                            $details[] = $detail->barang->nama_barang;
                        }
                        return implode(', ', $details);
                    })
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
                            ->when($data['from'], fn($q) => $q->whereDate('tgl_penjualan', '>=', $data['from']))
                            ->when($data['to'], fn($q) => $q->whereDate('tgl_penjualan', '<=', $data['to']));
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
            ->actions([])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanPenjualans::route('/'),
        ];
    }
}
