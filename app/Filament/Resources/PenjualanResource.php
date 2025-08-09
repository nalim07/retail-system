<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Barang;
use Filament\Forms\Form;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use Filament\Tables\Table;
use App\Models\PembelianDetail;
use App\Models\PenjualanDetail;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Actions\DeletePenjualan;
use App\Filament\Actions\DeletePenjualanBulkAction;
use App\Filament\Resources\PenjualanResource\Pages;
use App\Filament\Resources\PenjualanResource\RelationManagers\PenjualanDetailRelationManager;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Penjualan';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $pluralLabel = 'Penjualan';

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
                    ->options(Pelanggan::pluck('nama_pelanggan', 'id'))
                    ->searchable(),

                Forms\Components\Repeater::make('penjualanDetails')
                    ->label('Daftar Barang')
                    ->relationship('penjualanDetails')
                    ->schema([
                        Select::make('id_barang')
                            ->label('Barang')
                            ->options(\App\Models\Barang::pluck('nama_barang', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $barang = \App\Models\Barang::find($state);
                                $set('harga_jual', $barang?->harga_jual ?? 0);
                                // Tambahkan ini untuk mengisi satuan secara otomatis
                                if ($barang) {
                                    $set('satuan', $barang->satuan);
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('jumlah_penjualan')
                            ->label('Jumlah Penjualan')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->placeholder('Jumlah Barang yang dijual')
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $idBarang = $get('id_barang');

                                if (!$idBarang || !$state) return;

                                $totalSisa = \App\Models\PembelianDetail::where('id_barang', $idBarang)
                                    ->where('sisa', '>', 0)
                                    ->sum('sisa');

                                if ($state > $totalSisa) {
                                    Notification::make()
                                        ->title('Stok tidak mencukupi')
                                        ->body("Stok barang hanya tersedia {$totalSisa}, permintaan {$state}.")
                                        ->danger()
                                        ->send();

                                    $set('jumlah_penjualan', null); // reset input jika invalid
                                }
                            }),
                        TextInput::make('satuan')
                            ->required()
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Satuan terisi otomatis sesuai dengan satuan yang ada di tabel barang')
                            ->placeholder('contoh: pcs, kg')
                            ->reactive()
                            ->readOnly(),
                        TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->prefix('Rp')
                            ->readOnly(),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->required(),

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

                TextColumn::make('jumlah_total')
                    ->label('Jumlah Penjualan')
                    ->badge()
                    ->color('success'),

                TextColumn::make('penjualanDetails.harga_jual')
                    ->label('Harga Jual')
                    ->formatStateUsing(function ($state, $record) {
                        return number_format((float) $state, 0, ',', '.');
                    })
                    ->prefix('Rp')
                    ->sortable(),

                // TextColumn::make('total_harga')
                //     ->label('Total')
                //     ->prefix('Rp')
                //     ->numeric(0, ',', '.')
                //     ->state(function (Penjualan $record): float {
                //         return $record->penjualanDetails->sum(function ($detail) {
                //             return $detail->harga_jual * $detail->jumlah_penjualan;
                //         });
                //     }),

                // TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            // ->groups([
            //     Group::make('pelanggan.nama_pelanggan')
            //         ->label('Nama Pelanggan')
            //         ->collapsible(),
            // ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                DeletePenjualan::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeletePenjualanBulkAction::make(),
                ]),
            ]);
    }

    // public static function getRelations(): array
    // {
    //     return [
    //         PenjualanDetailRelationManager::class,
    //     ];
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjualans::route('/'),
            'create' => Pages\CreatePenjualan::route('/create'),
            'edit' => Pages\EditPenjualan::route('/{record}/edit'),
        ];
    }
}
