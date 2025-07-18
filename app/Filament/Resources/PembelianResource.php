<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Barang;
use Filament\Forms\Form;
use App\Models\Pembelian;
use Filament\Tables\Table;
use Illuminate\Support\Js;
use Filament\Support\RawJs;
use App\Models\KategoriBarang;
use Illuminate\Support\Carbon;
use App\Models\PembelianDetail;
use Filament\Resources\Resource;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Date;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;
use Filament\Forms\Components\Actions\Action;
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
    protected static ?string $pluralLabel = 'Pembelian';

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
                            ->options(Barang::pluck('nama_barang', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Ambil harga dari barang terpilih
                                $barang = \App\Models\Barang::find($state);
                                if ($barang) {
                                    $set('harga_beli', $barang->harga_barang);
                                }
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama_barang')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('jenis_barang')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('harga_barang')
                                    ->numeric()
                                    ->label('Harga (dalam Rupiah)')
                                    ->inputMode('numeric')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make(<<<'JS'
                                    $input => {
                                        let number = $input.replace(/\D/g, '');
                                            return new Intl.NumberFormat('id-ID').format(number);
                                        }
                                    JS))
                                    ->stripCharacters(['.', ','])
                                    ->required(),
                                Forms\Components\Select::make('id_kategori')
                                    ->label('Kategori')
                                    ->required()
                                    ->options(KategoriBarang::pluck('nama_kategori', 'id'))
                                    ->searchable(),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                // Logika untuk menyimpan data barang baru ke database
                                $barang = Barang::create($data);
                                return $barang->id;
                            }),
                        Forms\Components\TextInput::make('jumlah_pembelian')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('harga_beli')
                            ->numeric()
                            ->label('Harga Beli (Rupiah)')
                            ->prefix('Rp')
                            ->mask(RawJs::make(<<<'JS'
                                    $input => {
                                        let number = $input.replace(/\D/g, '');
                                        return new Intl.NumberFormat('id-ID').format(number);
                                    }
                                JS))
                            ->stripCharacters(['.', ','])
                            ->placeholder('Masukkan harga beli'),

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
            ->query(fn() => PembelianDetail::with(['barang', 'pembelian']))
            ->groups([
                Group::make('pembelian.tgl_pembelian')
                    ->label('Tanggal Pembelian')
                    ->collapsible()
                    ->getTitleFromRecordUsing(
                        fn($record): string =>
                        Carbon::parse($record->pembelian->tgl_pembelian)->translatedFormat('d F Y')
                    ),
            ])
            ->defaultGroup('pembelian.tgl_pembelian')
            ->columns([
                Tables\Columns\TextColumn::make('no')->rowIndex(),

                Tables\Columns\TextColumn::make('barang.nama_barang')
                    ->label('Barang')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?? 'Barang sudah dihapus'),

                Tables\Columns\TextColumn::make('jumlah_pembelian')
                    ->label('Jumlah Pembelian')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Harga')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable()
                    ->formatStateUsing(
                        fn($state) =>
                        is_numeric($state) ? number_format($state, 0, ',', '.') : '-'
                    ),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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
            // 'edit' => Pages\EditPembelian::route('/{record}/edit'),
        ];
    }
}
