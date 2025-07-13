<?php

namespace App\Filament\Resources\LaporanPembelianResource\Pages;

use App\Filament\Resources\LaporanPembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanPembelians extends ManageRecords
{
    protected static string $resource = LaporanPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
