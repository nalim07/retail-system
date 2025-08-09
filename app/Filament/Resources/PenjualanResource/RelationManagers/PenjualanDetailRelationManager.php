<?php

namespace App\Filament\Resources\PenjualanResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;

class PenjualanDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'penjualanDetails';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('id_barang')
                    ->label('Barang')
                    ->options(\App\Models\Barang::pluck('nama_barang', 'id'))
                    ->searchable()
                    ->required(),
                    
                TextInput::make('jumlah_penjualan')
                    ->label('Jumlah Penjualan')
                    ->numeric()
                    ->required(),
                    
                TextInput::make('harga_jual')
                    ->label('Harga Jual')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                    
                TextInput::make('satuan')
                    ->label('Satuan')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('jumlah_penjualan')
                    ->label('Jumlah')
                    ->numeric(),
                    
                TextColumn::make('satuan')
                    ->label('Satuan'),
                    
                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', '.'))
                    ->prefix('Rp'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}