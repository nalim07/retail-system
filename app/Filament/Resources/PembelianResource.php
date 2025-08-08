<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Barang;
use Filament\Forms\Form;
use App\Models\Pembelian;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\PembelianResource\Pages;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Pembelian';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $pluralLabel = 'Pembelian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('tgl_pembelian')
                    ->label('Tanggal Pembelian')
                    ->default(now())
                    ->required(),

                Forms\Components\Repeater::make('pembelianDetails') // Nama ini cocok dengan relasi
                    ->label('Daftar Barang')
                    ->relationship() // Filament otomatis tahu relasinya dari nama 'pembelianDetails'
                    ->schema([
                        Forms\Components\Select::make('id_barang')
                            ->label('Barang')
                            ->options(Barang::pluck('nama_barang', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $barang = Barang::find($state);
                                if ($barang) {
                                    $set('satuan', $barang->satuan);
                                    $set('harga_jual', $barang->harga_jual);
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('jumlah_pembelian')
                            ->label('Jumlah Pembelian')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->placeholder('Masukkan jumlah pembelian'),
                        Forms\Components\TextInput::make('satuan')
                            ->required()
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Satuan terisi otomatis sesuai dengan satuan yang ada di tabel barang')
                            ->placeholder('contoh: pcs, kg')
                            ->reactive()
                            ->readOnly(),
                        Forms\Components\TextInput::make('harga_beli')
                            ->numeric()
                            ->label('Harga Beli')
                            ->prefix('Rp')
                            ->required(),
                        Forms\Components\TextInput::make('harga_jual')
                            ->numeric()
                            ->label('Harga Jual')
                            ->prefix('Rp')
                            ->required()
                            ->reactive()
                            ->afterStateHydrated(function (callable $set, callable $get) {
                                if (is_null($get('harga_jual')) && $get('id_barang')) {
                                    $barang = Barang::find($get('id_barang'));
                                    if ($barang) {
                                        $set('harga_jual', $barang->harga_jual);
                                    }
                                }
                            }),
                    ])
                    ->columns(2)
                    ->addable()
                    ->deletable()
                    ->reorderable()
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Tidak perlu ->query() lagi, karena sudah diatur oleh $model
            ->columns([
                Tables\Columns\TextColumn::make('tgl_pembelian')
                    ->label('Tanggal Pembelian')
                    ->date('d F Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pembelianDetails.barang.nama_barang')
                    ->label('Barang')
                    ->sortable()
                    ->searchable(),

                // Menampilkan jumlah item dalam setiap pembelian
                Tables\Columns\TextColumn::make('pembelianDetails.jumlah_pembelian')
                    ->counts('pembelianDetails')
                    ->label('Jumlah Barang')
                    ->badge(),

                Tables\Columns\TextColumn::make('pembelianDetails.harga_beli')
                    ->label('Harga Beli')
                    ->numeric()
                    ->prefix('Rp')
                    ->sortable(),

                // (Opsional) Menampilkan total nilai pembelian
                // Tables\Columns\TextColumn::make('total_harga')
                //     ->label('Total Pembelian')
                //     ->prefix('Rp ')
                //     ->numeric(0, ',', '.')
                //     ->state(function (Pembelian $record): float {
                //         // Menghitung total dari detail
                //         return $record->pembelianDetails->sum(function ($detail) {
                //             return $detail->harga_beli * $detail->jumlah_pembelian;
                //         });
                //     }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    // ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
