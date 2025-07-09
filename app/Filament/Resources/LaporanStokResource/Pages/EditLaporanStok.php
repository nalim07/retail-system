<?php

namespace App\Filament\Resources\LaporanStokResource\Pages;

use App\Filament\Resources\LaporanStokResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaporanStok extends EditRecord
{
    protected static string $resource = LaporanStokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
