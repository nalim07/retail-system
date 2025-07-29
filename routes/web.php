<?php

use App\Models\PembelianDetail;
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
    ])->stream('laporan-stok.pdf'); // stream = preview, download = force download
})->name('laporan-stok.preview');
