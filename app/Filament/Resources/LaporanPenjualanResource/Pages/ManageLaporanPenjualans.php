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
                ->url(fn(): string => route('laporan-penjualan.preview', [
                    'tableFilters' => [
                        'tanggal' => [
                            'from' => $this->tableFilters['tanggal']['from'] ?? null,
                            'to' => $this->tableFilters['tanggal']['to'] ?? null,
                        ],
                        'pelanggan' => $this->tableFilters['pelanggan'] ?? null,
                    ],
                ]))
                ->openUrlInNewTab(),
        ];
    }
}
