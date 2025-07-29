<?php

namespace App\Filament\Resources\LaporanPenjualanResource\Pages;

use App\Filament\Resources\LaporanPenjualanResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\Action;

class ManageLaporanPenjualans extends ManageRecords
{
    protected static string $resource = LaporanPenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->url(fn() => route('laporan-penjualan.preview', [
                    'from' => now()->subMonth()->format('Y-m-d'),
                    'to' => now()->format('Y-m-d'),
                ]))
                ->openUrlInNewTab(),
        ];
    }
}
