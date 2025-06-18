<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Pembelian;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Date;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;
use App\Filament\Resources\PembelianResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PembelianResource\RelationManagers;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Pembelian';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('tgl_pembelian')
                    ->label('Tanggal Pembelian')
                    ->default(Date::now())
                    ->required(),

                Forms\Components\Repeater::make('pembelianDetails')
                    ->label('Daftar Barang')
                    ->relationship('pembelianDetails')
                    ->schema([
                        Forms\Components\Select::make('id_barang')
                            ->label('Barang')
                            ->options(\App\Models\Barang::pluck('nama_barang', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('jumlah_pembelian')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('harga_satuan')
                            ->numeric()
                            ->required()
                            ->inputMode('numeric')
                            ->prefix('Rp')
                            ->mask(RawJs::make(<<<'JS'
                                $input => {
                                    let number = $input.replace(/\D/g, '');
                                    return new Intl.NumberFormat('id-ID').format(number);
                                }
                            JS))
                            ->stripCharacters(['.', ','])
                            ->placeholder('Masukkan harga satuan'),
                        Forms\Components\TextInput::make('sisa')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->placeholder('Sisa stok')
                            ->mask(RawJs::make(<<<'JS'
                                $input => {
                                    let number = $input.replace(/\D/g, '');
                                    return new Intl.NumberFormat('id-ID').format(number);
                                }
                            JS))
                            ->stripCharacters(['.', ',']),
                    ])
                    ->columns(2),

            ])->columns([
                'lg' => 1,
                'md' => 1,
                'sm' => 1,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tgl_pembelian')
                    ->label('Tanggal Pembelian')
                    ->date()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('pembelianDetails.barang.nama_barang')
                    ->label('Barang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pembelianDetails.jumlah_pembelian')
                    ->label('Jumlah Pembelian')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pembelianDetails.harga_satuan')
                    ->label('Harga Satuan')
                    ->numeric()
                    ->prefix('Rp')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.')),
                Tables\Columns\TextColumn::make('pembelianDetails.sisa')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->default('0'),
                // Tables\Columns\TextColumn::make('pembelianDetails.harga_satuan')
                //     ->label('Total Harga')
                //     ->numeric()
                //     ->prefix('Rp')
                //     ->sortable()
                //     ->formatStateUsing(function ($state, $record) {
                //         return number_format($record->pembelianDetails->sum(function ($detail) {
                //             return $detail->jumlah_pembelian * $detail->harga_satuan;
                //         }), 0, ',', '.');
                //     }),
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
            'index' => Pages\ListPembelians::route('/'),
            'create' => Pages\CreatePembelian::route('/create'),
            'edit' => Pages\EditPembelian::route('/{record}/edit'),
        ];
    }
}
