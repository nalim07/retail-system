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
