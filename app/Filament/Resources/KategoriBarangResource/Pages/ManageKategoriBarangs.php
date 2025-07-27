<?php

namespace App\Filament\Resources\KategoriBarangResource\Pages;

use App\Filament\Resources\KategoriBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageKategoriBarangs extends ManageRecords
{
    protected static string $resource = KategoriBarangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah')
                ->modalWidth('md')
                ->modalSubmitActionLabel('Tambah')
                ->modalHeading('Tambah Kategori Barang')
                ->disableCreateAnother(),
        ];
    }
}
