# Implementasi Metode FIFO dalam Sistem Retail

## Gambaran Umum

Metode FIFO (First In, First Out) adalah pendekatan manajemen inventaris di mana barang yang pertama kali masuk akan menjadi barang yang pertama kali keluar. Dalam sistem ini, implementasi FIFO memastikan bahwa barang dengan tanggal kedaluwarsa paling awal dijual terlebih dahulu, mengurangi risiko barang menjadi kadaluarsa atau usang.

## Struktur Data untuk FIFO

### Tabel PembelianDetail
Tabel ini merupakan inti dari implementasi FIFO, dengan field khusus:
- `sisa`: Melacak jumlah barang yang masih tersedia dari batch pembelian ini
- `id_pembelian`: Referensi ke header pembelian
- `id_barang`: Referensi ke barang yang dibeli
- `jumlah_pembelian`: Jumlah barang yang dibeli dalam batch ini
- `harga_beli`: Harga pembelian per unit

### Tabel PenjualanDetail
Tabel ini menghubungkan penjualan dengan batch pembelian yang digunakan:
- `id_penjualan`: Referensi ke header penjualan
- `id_barang`: Referensi ke barang yang dijual
- `id_pembelian_detail`: Referensi ke batch pembelian yang digunakan (implementasi FIFO)
- `jumlah_penjualan`: Jumlah barang yang dijual

## Alur Implementasi FIFO

### 1. Saat Pembelian Dicatat
1. Sistem membuat record baru di tabel `pembelian`
2. Untuk setiap item dalam pembelian, sistem membuat record di `pembelian_detail`
3. Field `sisa` diatur sama dengan `jumlah_pembelian`, menunjukkan bahwa semua item masih tersedia
4. Stok total di tabel `barang` diperbarui dengan menambahkan jumlah pembelian

### 2. Saat Penjualan Dibuat
1. Sistem memvalidasi ketersediaan stok dengan menjumlahkan field `sisa` dari semua batch pembelian untuk barang yang diminta
2. Dalam metode `afterCreate()` di `CreatePenjualan.php`, sistem:
   - Mengambil batch pembelian berdasarkan tanggal (paling awal dulu) menggunakan `orderBy('pembelian.tgl_pembelian')`
   - Untuk setiap item dalam penjualan, sistem:
     - Mengambil batch pembelian yang masih memiliki stok (`sisa > 0`)
     - Menggunakan barang dari batch yang paling lama tersedia
     - Mengurangi field `sisa` di `PembelianDetail` sesuai jumlah yang dijual
     - Mengurangi stok total di `Barang`
     - Mencatat referensi ke batch pembelian yang digunakan di `PenjualanDetail.id_pembelian_detail`

### 3. Saat Penjualan Diedit
1. Sistem menyimpan data lama sebelum perubahan
2. Untuk item yang dihapus: mengembalikan stok ke batch pembelian yang sesuai
3. Untuk item yang diubah: mengembalikan stok lama dan mengalokasikan ulang berdasarkan FIFO
4. Untuk item baru: mengalokasikan stok berdasarkan FIFO seperti saat pembuatan

### 4. Saat Penjualan Dihapus
1. Sistem menggunakan custom action `DeletePenjualan`
2. Untuk setiap detail penjualan, sistem:
   - Mengembalikan stok ke batch pembelian yang sesuai (meningkatkan field `sisa`)
   - Mengembalikan stok total di tabel `barang`
   - Menghapus riwayat penjualan terkait

## Kode Kunci FIFO

### Algoritma FIFO dalam CreatePenjualan.php
```php
// FIFO - ambil batch pembelian paling awal
$batchList = PembelianDetail::where('id_barang', $barang->id)
    ->where('sisa', '>', 0)
    ->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian')
    ->orderBy('pembelian.tgl_pembelian')
    ->select('pembelian_detail.*')
    ->get();

foreach ($batchList as $batch) {
    if ($jumlah <= 0) break;

    $dipakai = min($jumlah, $batch->sisa);

    $batch->sisa -= $dipakai;
    $batch->save();

    $barang->stok -= $dipakai;
    $barang->save();

    // Update detail penjualan dengan referensi batch pembelian yang digunakan
    $detail->update([
        'id_pembelian_detail' => $batch->id,
        'jumlah_penjualan' => $dipakai,
    ]);

    $jumlah -= $dipakai;
}
```

### Pengembalian Stok dalam DeletePenjualan.php
```php
// Mengembalikan stok ke pembelian detail
$pembelianDetail = PembelianDetail::find($idPembelianDetail);
if ($pembelianDetail) {
    $pembelianDetail->sisa += $jumlah;
    $pembelianDetail->save();
}

// Kembalikan stok barang
$barang = Barang::find($idBarang);
if ($barang) {
    $barang->stok += $jumlah;
    $barang->save();
}
```

## Keuntungan Implementasi FIFO

1. **Manajemen Stok yang Efektif**: Mencegah barang menjadi kadaluarsa dengan menjual barang yang paling lama tersedia terlebih dahulu
2. **Pelacakan yang Akurat**: Setiap transaksi penjualan dapat dilacak kembali ke batch pembelian tertentu
3. **Transparansi Biaya**: Memungkinkan perhitungan harga pokok penjualan yang akurat berdasarkan harga pembelian aktual
4. **Kepatuhan Regulasi**: Sesuai dengan prinsip akuntansi yang mensyaratkan penggunaan metode FIFO untuk inventaris

## Pertimbangan Teknis

1. **Performa**: Query untuk mengambil batch pembelian berdasarkan tanggal memerlukan join dan ordering
2. **Integritas Data**: Sistem menggunakan transaksi database untuk memastikan konsistensi data saat mengupdate stok
3. **Validasi Stok**: Sistem memvalidasi ketersediaan stok sebelum menyimpan transaksi penjualan
4. **Penanganan Kesalahan**: Sistem memberikan notifikasi jika stok tidak mencukupi saat pembuatan penjualan
