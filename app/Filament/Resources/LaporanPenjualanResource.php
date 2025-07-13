<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Penjualan;
use Filament\Tables\Table;
use App\Models\LaporanPenjualan;
use Filament\Resources\Resource;
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
                Tables\Columns\TextColumn::make('penjualanDetails')
                    ->label('Total Barang')
                    ->formatStateUsing(function ($state, $record) {
                        $details = [];
                        foreach ($record->penjualanDetails as $detail) {
                            $details[] = $detail->jumlah_penjualan . ' ' . $detail->barang->nama_barang;
                        }
                        return implode(', ', $details);
                    })
                    ->sortable(),
                // nama barang
                Tables\Columns\TextColumn::make('penjualanDetails')
                    ->label('Nama Barang')
                    ->formatStateUsing(function ($state, $record) {
                        $details = [];
                        foreach ($record->penjualanDetails as $detail) {
                            $details[] = $detail->barang->nama_barang;
                        }
                        return implode(', ', $details);
                    })
                    ->sortable(),
                // total barang
                Tables\Columns\TextColumn::make('penjualanDetails')
                    ->label('Total Barang')
                    ->formatStateUsing(function ($state, $record) {
                        $details = [];
                        foreach ($record->penjualanDetails as $detail) {
                            $details[] = $detail->jumlah_penjualan;
                        }
                        return implode(', ', $details);
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                
            ])
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
