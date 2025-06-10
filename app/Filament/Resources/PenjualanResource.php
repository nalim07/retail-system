<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Penjualan;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\PenjualanResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PenjualanResource\RelationManagers;

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Penjualan';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('tgl_penjualan')
                    ->label('Tanggal Penjualan')
                    ->default(now())
                    ->required(),

                Select::make('id_pelanggan')
                    ->label('Pelanggan')
                    ->options(\App\Models\Pelanggan::pluck('nama_pelanggan', 'id'))
                    // ->relationship('pelanggan', 'nama_pelanggan')
                    ->searchable()
                    ->required(),

                Repeater::make('penjualanDetails')
                    ->label('Daftar Barang')
                    ->relationship('penjualanDetails')
                    ->schema([
                        Select::make('id_barang')
                            ->label('Barang')
                            ->options(\App\Models\Barang::pluck('nama_barang', 'id'))
                            // ->relationship('barang', 'nama_barang')
                            ->searchable()
                            ->required(),
                        TextInput::make('jumlah_penjualan')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columnSpan('full')
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Barang'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Penjualan::with(['penjualanDetails.barang', 'pelanggan'])
            )
            ->columns([
                TextColumn::make('tgl_penjualan')
                    ->label('Tanggal')
                    ->dateTime('d M Y'),

                TextColumn::make('pelanggan.nama_pelanggan')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('penjualanDetails.barang.nama_barang')
                    ->label('Barang')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->penjualanDetails->pluck('barang.nama_barang')->join(', ');
                    }),


                TextColumn::make('jumlah_total')
                    ->label('Jumlah')
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjualans::route('/'),
            'create' => Pages\CreatePenjualan::route('/create'),
            'edit' => Pages\EditPenjualan::route('/{record}/edit'),
        ];
    }
}
