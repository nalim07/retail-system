<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Pembelian;
use Filament\Tables\Table;
use App\Models\LaporanPembelian;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LaporanPembelianResource\Pages;
use App\Filament\Resources\LaporanPembelianResource\RelationManagers;

class LaporanPembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Laporan Pembelian';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 2;

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
                Tables\Columns\TextColumn::make('tgl_pembelian')
                    ->label('Tanggal Pembelian')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pembelianDetails.barang.nama_barang')
                    ->label('Nama Barang')
                    ->formatStateUsing(function ($state, $record) {
                        $details = [];
                        foreach ($record->pembelianDetails as $detail) {
                            $details[] = $detail->barang->nama_barang;
                        }
                        return implode(', ', $details);
                    }),
                // Tables\Columns\TextColumn::make('total_harga')
                //     ->label('Total Harga')
                //     ->money('id_ID')
                //     ->sortable(),
                Tables\Columns\TextColumn::make('pembelianDetails')
                    ->label('Total Barang')
                    ->formatStateUsing(function ($state, $record) {
                        $details = [];
                        foreach ($record->pembelianDetails as $detail) {
                            $details[] = $detail->jumlah_pembelian . ' ' . $detail->barang->nama_barang;
                        }
                        return implode(', ', $details);
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
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
