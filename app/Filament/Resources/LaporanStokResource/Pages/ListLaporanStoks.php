<?php

namespace App\Filament\Resources\LaporanStokResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\LaporanStokResource;

class ListLaporanStoks extends ListRecords
{
    protected static string $resource = LaporanStokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->url(fn($livewire) => route('laporan-stok.preview', [
                    'from' => $livewire->tableFilters['tanggal']['from'] ?? null,
                    'to' => $livewire->tableFilters['tanggal']['to'] ?? null,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    // public function generatePdf()
    // {
    //     $filters = $this->tableFilters['tanggal'] ?? [];

    //     $query = \App\Models\PembelianDetail::query();

    //     if (!empty($filters['from'])) {
    //         $query->whereHas(
    //             'pembelian',
    //             fn($q) =>
    //             $q->whereDate('tgl_pembelian', '>=', $filters['from'])
    //         );
    //     }

    //     if (!empty($filters['to'])) {
    //         $query->whereHas(
    //             'pembelian',
    //             fn($q) =>
    //             $q->whereDate('tgl_pembelian', '<=', $filters['to'])
    //         );
    //     }

    //     $data = $query->with('barang.kategori', 'pembelian')->get();

    //     $pdf = Pdf::loadView('laporan-stok', compact('data'));

    //     return response()->streamDownload(
    //         fn() => print($pdf->output()),
    //         'laporan-stok.pdf',
    //         ['Content-Type' => 'application/pdf']
    //     );
    // }
}
