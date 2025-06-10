<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Barang;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use App\Models\KategoriBarang;
use Filament\Resources\Resource;
use Filament\Forms\Components\Mask;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BarangResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BarangResource\RelationManagers;

class BarangResource extends Resource
{
    protected static ?string $model = Barang::class;

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Barang';
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_barang')
                    ->required()
                    ->maxLength(255),
                TextInput::make('jenis_barang')
                    ->required()
                    ->maxLength(255),
                TextInput::make('stok')
                    ->required()
                    ->numeric(),
                TextInput::make('harga')
                    ->required()
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
                    ->placeholder('Masukkan harga barang'),
                Select::make('id_kategori')
                    ->label('Kategori')
                    ->required()
                    ->options(KategoriBarang::all()->pluck('nama_kategori', 'id'))
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_barang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jenis_barang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stok')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('harga')
                    ->numeric()
                    ->sortable()
                    ->prefix('Rp'),
                Tables\Columns\TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListBarangs::route('/'),
            'create' => Pages\CreateBarang::route('/create'),
            'edit' => Pages\EditBarang::route('/{record}/edit'),
        ];
    }
}
