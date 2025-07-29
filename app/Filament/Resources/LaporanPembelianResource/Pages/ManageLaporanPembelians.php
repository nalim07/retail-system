<?php

namespace App\Filament\Resources\LaporanPembelianResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Resources\LaporanPembelianResource;

class ManageLaporanPembelians extends ManageRecords
{
    protected static string $resource = LaporanPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->url(fn(): string => route('laporan-pembelian.preview', [
                    'tableFilters' => [
                        'tanggal' => [
                            'from' => $this->tableFilters['tanggal']['from'] ?? null,
                            'to' => $this->tableFilters['tanggal']['to'] ?? null,
                        ],
                    ],
                ]))

                ->openUrlInNewTab(),
        ];
    }
}
