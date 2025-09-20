<?php

use App\Models\PembelianDetail;
use App\Models\PenjualanDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/laporan-stok/preview', function () {
    $query = PembelianDetail::with('barang.kategori', 'pembelian');

    if (request('from')) {
        $query->whereHas('pembelian', fn($q) =>
        $q->whereDate('tgl_pembelian', '>=', request('from')));
    }

    if (request('to')) {
        $query->whereHas('pembelian', fn($q) =>
        $q->whereDate('tgl_pembelian', '<=', request('to')));
    }

    // Jika tidak ada filter, bisa diabaikan atau set kosong:
    // $query->whereRaw('1 = 0');

    $data = $query->get();

    return Pdf::loadView('prints.laporan-stok', [
        'data' => $data,
        'from' => request('from'),
        'to' => request('to'),
    ])->stream('laporan-stok.pdf');
})->name('laporan-stok.preview');

Route::get('/laporan-penjualan/preview', function () {
    // Ambil filter dari request (baik dari tableFilters atau from/to langsung)
    $filters = request('tableFilters') ?? [];

    $from = $filters['tanggal']['from'] ?? request('from');
    $to = $filters['tanggal']['to'] ?? request('to');
    $pelanggan = $filters['pelanggan'] ?? request('pelanggan');

    // Gunakan PenjualanDetail untuk laporan penjualan
    $query = PenjualanDetail::with(['penjualan', 'barang'])
        ->whereHas('penjualan', function($q) use ($from, $to, $pelanggan) {
            if ($from) {
                $q->whereDate('tgl_penjualan', '>=', $from);
            }
            if ($to) {
                $q->whereDate('tgl_penjualan', '<=', $to);
            }
            if ($pelanggan) {
                $q->whereHas('pelanggan', function($q) use ($pelanggan) {
                    $q->where('nama_pelanggan', $pelanggan);
                });
            }
        });

    $data = $query->get()->groupBy(function($item) {
        return $item->penjualan->pelanggan ? $item->penjualan->pelanggan->nama_pelanggan : '-';
    });

    return Pdf::loadView('prints.laporan-penjualan', [
        'data' => $data,
        'from' => $from,
        'to' => $to,
        'pelanggan' => $pelanggan,
    ])->stream('laporan-penjualan.pdf');
})->name('laporan-penjualan.preview');

Route::get('/laporan-pembelian/preview', function () {
    $filters = request('tableFilters') ?? [];

    $from = $filters['tanggal']['from'] ?? null;
    $to = $filters['tanggal']['to'] ?? null;

    // Gunakan PembelianDetail untuk laporan pembelian
    $query = PembelianDetail::with(['pembelian', 'barang'])
        ->whereHas('pembelian', function($q) use ($from, $to) {
            if ($from) {
                $q->whereDate('tgl_pembelian', '>=', $from);
            }
            if ($to) {
                $q->whereDate('tgl_pembelian', '<=', $to);
            }
        });

    $data = $query->get();

    return Pdf::loadView('prints.laporan-pembelian', [
        'data' => $data,
        'from' => $from,
        'to' => $to,
    ])->stream('laporan-pembelian.pdf');
})->name('laporan-pembelian.preview');

Route::get('/fifo-visualization/preview', function () {
    // Ambil SEMUA data pembelian detail dengan stok > 0 dari seluruh sistem
    $records = PembelianDetail::query()
        ->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian')
        ->join('barang', 'barang.id', '=', 'pembelian_detail.id_barang')
        ->with(['barang', 'pembelian'])
        ->select('pembelian_detail.*')
        ->where('pembelian_detail.sisa', '>', 0)
        ->orderBy('barang.nama_barang', 'asc')
        ->orderBy('pembelian.tgl_pembelian', 'asc')
        ->get();

    // Create Word document
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection([
        'orientation' => 'portrait',
        'marginLeft' => 600,
        'marginRight' => 600,
        'marginTop' => 600,
        'marginBottom' => 600,
    ]);

    // Start directly with content (no header)

    // Group records by product
    $groupedRecords = $records->groupBy('barang.nama_barang');

    // FIFO Boxes Visualization - Individual unit boxes for each stock item
    foreach ($groupedRecords as $namaBarang => $barangRecords) {
        // Product title with better styling
        $section->addText($namaBarang, [
            'bold' => true, 
            'size' => 16, 
            'name' => 'Arial',
            'color' => '2563EB'
        ]);
        $section->addTextBreak();

        // Process each batch
        foreach ($barangRecords as $batchIndex => $record) {
            // Batch header
            $section->addText('BATCH ' . ($batchIndex + 1) . ' - ' . \Carbon\Carbon::parse($record->pembelian->tgl_pembelian)->format('d/m/Y'), [
                'bold' => true, 
                'size' => 14,
                'color' => $batchIndex === 0 ? '16A34A' : '3B82F6',
                'name' => 'Arial'
            ]);
            $section->addTextBreak();

            // Create individual boxes for each unit in stock
            $stockQuantity = (int) $record->sisa;
            $boxesPerRow = 10; // 10 small boxes per row for better visualization
            $currentBoxIndex = 0;
            
            // Limit display for very large quantities to prevent document bloat
            $maxBoxesToShow = min($stockQuantity, 100); // Show max 100 boxes
            $showingLimited = $stockQuantity > 100;
            
            // Create container table for unit boxes
            $containerTable = $section->addTable([
                'borderSize' => 0,
                'cellMargin' => 20,
                'width' => 100 * 50,
            ]);
            
            $currentRow = null;
            
            // Create individual unit boxes
            for ($unitIndex = 0; $unitIndex < $maxBoxesToShow; $unitIndex++) {
                if ($currentBoxIndex % $boxesPerRow == 0) {
                    $currentRow = $containerTable->addRow();
                }

                // Create individual unit box cell
                $unitCell = $currentRow->addCell(400);
                
                // Create small box for each unit
                $unitTable = $unitCell->addTable([
                    'borderSize' => 8,
                    'borderColor' => $batchIndex === 0 ? '16A34A' : '3B82F6',
                    'cellMargin' => 30,
                    'width' => 400,
                ]);

                $unitRow = $unitTable->addRow(400);
                
                // Unit box styling
                $unitBoxStyle = $batchIndex === 0 ? 
                    [
                        'bgColor' => 'DCFCE7', 
                        'borderSize' => 8, 
                        'borderColor' => '16A34A'
                    ] : 
                    [
                        'bgColor' => 'DBEAFE', 
                        'borderSize' => 6, 
                        'borderColor' => '3B82F6'
                    ];

                $unitBoxCell = $unitRow->addCell(400, $unitBoxStyle);
                
                // Unit number in the box - should show batch number, not unit sequence
                $unitBoxCell->addText(($batchIndex + 1), [
                    'bold' => true, 
                    'size' => 8,
                    'color' => $batchIndex === 0 ? '16A34A' : '3B82F6',
                    'name' => 'Arial'
                ], ['alignment' => 'center']);

                $currentBoxIndex++;
            }
            
            // Fill remaining cells in the last row if needed
            while ($currentBoxIndex % $boxesPerRow != 0) {
                $currentRow->addCell(400);
                $currentBoxIndex++;
            }

            // Show summary if quantity is limited
            if ($showingLimited) {
                $section->addTextBreak();
                $section->addText('... dan ' . ($stockQuantity - $maxBoxesToShow) . ' unit lainnya', [
                    'italic' => true,
                    'size' => 10,
                    'color' => '6B7280'
                ], ['alignment' => 'center']);
            }

            // Batch summary
            $section->addTextBreak();
            $summaryTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => 'E5E7EB',
                'cellMargin' => 80,
                'width' => 100 * 50,
            ]);
            
            $summaryRow = $summaryTable->addRow();
            
            // Total units
            $totalCell = $summaryRow->addCell(2000, ['bgColor' => 'F9FAFB']);
            $totalCell->addText('Total Unit: ' . number_format($stockQuantity, 0, ',', '.'), [
                'bold' => true,
                'size' => 11,
                'color' => '374151'
            ]);
            
            // Purchase price
            $priceCell = $summaryRow->addCell(2000, ['bgColor' => 'F9FAFB']);
            $priceCell->addText('Harga Beli: Rp ' . number_format($record->harga_beli, 0, ',', '.'), [
                'bold' => true,
                'size' => 11,
                'color' => '374151'
            ]);
            
            // Status
            $statusCell = $summaryRow->addCell(2000, ['bgColor' => 'F9FAFB']);
            $statusText = $batchIndex === 0 ? 'PRIORITAS UTAMA' : 'MENUNGGU GILIRAN';
            $statusCell->addText($statusText, [
                'bold' => true,
                'size' => 11,
                'color' => $batchIndex === 0 ? '16A34A' : '3B82F6'
            ]);

            $section->addTextBreak(2);
        }

        $section->addTextBreak(3);
    }

    // Summary sections (keep existing summary logic)
    $section->addText('Ringkasan per Barang', ['bold' => true, 'size' => 14, 'name' => 'Arial']);
    $section->addTextBreak();

    foreach ($groupedRecords as $namaBarang => $barangRecords) {
        $section->addText('Ringkasan ' . $namaBarang, ['bold' => true, 'size' => 12]);
        $section->addText('Total Batch: ' . $barangRecords->count() . ' batch');
        $section->addText('Total Stok: ' . number_format($barangRecords->sum('sisa'), 0, ',', '.') . ' ' . 
            ($barangRecords->first()->barang->satuan ?? 'pcs'));
        $section->addText('Total Nilai: Rp ' . number_format($barangRecords->sum(function ($r) {
            return $r->sisa * $r->harga_beli;
        }), 0, ',', '.'));
        $section->addText('Rata-rata Harga: Rp ' . number_format($barangRecords->avg('harga_beli'), 0, ',', '.'));
        $section->addTextBreak();
    }

    // Overall summary
    $totalBarang = $groupedRecords->count();
    $totalBatch = $records->count();
    $totalStok = $records->sum('sisa');
    $totalNilai = $records->sum(function ($record) {
        return $record->sisa * $record->harga_beli;
    });

    $section->addText('Ringkasan Keseluruhan', ['bold' => true, 'size' => 14]);
    $section->addText('Total Jenis Barang: ' . $totalBarang . ' jenis');
    $section->addText('Total Batch: ' . $totalBatch . ' batch');
    $section->addText('Total Stok: ' . number_format($totalStok, 0, ',', '.') . ' unit');
    $section->addText('Total Nilai Stok: Rp ' . number_format($totalNilai, 0, ',', '.'));

    // Save and download
    $filename = 'fifo-visualization-' . date('Y-m-d-H-i-s') . '.docx';
    $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
    
    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($tempFile);

    return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
})->name('fifo-visualization.preview');
