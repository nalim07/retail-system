<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelangganResource\Pages;
use App\Filament\Resources\PelangganResource\RelationManagers;
use App\Models\Pelanggan;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PelangganResource extends Resource
{
    protected static ?string $model = Pelanggan::class;

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Pelanggan';
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_pelanggan')
                    ->required()
                    ->maxLength(25)
                    ->label('Nama Pelanggan')
                    ->inputMode('text')
                    ->placeholder('Masukkan nama lengkap'),
                Forms\Components\TextInput::make('alamat')
                    ->required()
                    ->maxLength(255)
                    ->label('Alamat')
                    ->inputMode('text')
                    ->placeholder('Masukkan alamat pelanggan'),
                Forms\Components\TextInput::make('no_hp')
                    ->required()
                    ->maxLength(15)
                    ->label('No. HP')
                    ->inputMode('tel')
                    ->regex('/^\+?[0-9\s\-()]+$/')
                    ->placeholder('Contoh: 081234567890'),
            ])->columns([
                'sm' => 1,
                'md' => 1,
                'lg' => 1,
                'xl' => 1,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('nama_pelanggan')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Pelanggan'),
                Tables\Columns\TextColumn::make('alamat')
                    ->searchable()
                    ->sortable()
                    ->label('Alamat'),
                Tables\Columns\TextColumn::make('no_hp')
                    ->searchable()
                    ->sortable()
                    ->label('No. HP'),
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePelanggans::route('/'),
        ];
    }
}
