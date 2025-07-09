<?php

namespace App\Filament\Resources\LaporanStokResource\Pages;

use App\Filament\Resources\LaporanStokResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaporanStoks extends ListRecords
{
    protected static string $resource = LaporanStokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
