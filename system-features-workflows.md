# Fitur dan Alur Kerja Sistem Retail dengan FIFO

## Fitur Utama Sistem

### 1. Master Data
- **Pelanggan**: Pengelolaan data pelanggan termasuk nama, alamat, dan informasi kontak
- **Kategori Barang**: Pengelompokan barang berdasarkan kategori untuk memudahkan pengelolaan
- **Barang**: Pengelolaan data barang termasuk nama, stok, harga jual, dan kategori

### 2. Transaksi
- **Pembelian (Restok)**: Mencatat transaksi pembelian atau restok barang dari supplier dengan implementasi FIFO
- **Penjualan**: Mencatat transaksi penjualan kepada pelanggan dengan implementasi metode FIFO

### 3. Laporan
- **Laporan Stok**: Menampilkan informasi stok barang terkini berdasarkan batch pembelian
- **Laporan Penjualan**: Menampilkan riwayat dan analisis penjualan
- **Laporan Pembelian**: Menampilkan riwayat dan analisis pembelian

## Alur Kerja Sistem

### Alur Pembelian (Restok)
1. **Input Data**: Admin mengakses menu Pembelian dan membuat transaksi baru
2. **Pencatatan Detail**: Admin memasukkan detail barang yang dibeli, termasuk jumlah, harga beli, dan harga jual
3. **Pemrosesan Sistem**:
   - Sistem membuat record header pembelian di tabel `pembelian`
   - Untuk setiap item, sistem membuat record detail di tabel `pembelian_detail`
   - Field `sisa` di `pembelian_detail` diatur sama dengan jumlah pembelian
   - Stok total di tabel `barang` diperbarui dengan menambahkan jumlah pembelian
   - Riwayat pembelian dicatat di tabel `riwayat_pembelians`
4. **Konfirmasi**: Sistem menampilkan konfirmasi bahwa pembelian berhasil dicatat

### Alur Penjualan
1. **Input Data**: Admin mengakses menu Penjualan dan membuat transaksi baru
2. **Validasi Stok**: Sistem memvalidasi ketersediaan stok berdasarkan field `sisa` di `pembelian_detail`
3. **Pencatatan Detail**: Admin memasukkan detail barang yang dijual
4. **Pemrosesan FIFO**:
   - Sistem mengambil batch pembelian berdasarkan tanggal (paling awal dulu)
   - Untuk setiap item, sistem mengurangi stok dari batch yang paling lama tersedia
   - Field `sisa` di `pembelian_detail` diperbarui
   - Stok total di tabel `barang` diperbarui
   - Referensi ke batch pembelian dicatat di `penjualan_detail.id_pembelian_detail`
   - Riwayat penjualan dicatat di tabel `riwayat_penjualans`
5. **Konfirmasi**: Sistem menampilkan konfirmasi bahwa penjualan berhasil dicatat

### Alur Pengeditan Penjualan
1. **Akses Data**: Admin membuka transaksi penjualan yang akan diedit
2. **Penyimpanan Data Lama**: Sistem menyimpan data lama untuk referensi
3. **Pemrosesan Perubahan**:
   - Untuk item yang dihapus: mengembalikan stok ke batch pembelian yang sesuai
   - Untuk item yang diubah: mengembalikan stok lama dan mengalokasikan ulang berdasarkan FIFO
   - Untuk item baru: mengalokasikan stok berdasarkan FIFO seperti saat pembuatan
4. **Pembaruan Riwayat**: Riwayat penjualan diperbarui sesuai perubahan

### Alur Penghapusan Penjualan
1. **Konfirmasi**: Admin mengkonfirmasi penghapusan transaksi penjualan
2. **Pengembalian Stok**: Sistem mengembalikan stok ke batch pembelian yang sesuai
3. **Pembaruan Data**:
   - Field `sisa` di `pembelian_detail` diperbarui
   - Stok total di tabel `barang` diperbarui
   - Riwayat penjualan dihapus
4. **Konfirmasi**: Sistem menampilkan konfirmasi bahwa penjualan berhasil dihapus

## Fitur Keamanan dan Validasi

### Validasi Stok
- Sistem memvalidasi ketersediaan stok sebelum menyimpan transaksi penjualan
- Notifikasi ditampilkan jika stok tidak mencukupi

### Transaksi Database
- Semua operasi yang melibatkan perubahan stok menggunakan transaksi database untuk memastikan konsistensi data

### Notifikasi Pengguna
- Sistem memberikan notifikasi untuk setiap operasi penting (berhasil/gagal)
- Notifikasi kesalahan ditampilkan jika terjadi masalah saat pemrosesan

## Integrasi dengan Filament

### Antarmuka Admin
- Menggunakan Filament 3.x sebagai panel admin
- Responsive dan modern dengan berbagai komponen UI
- Navigasi terstruktur berdasarkan jenis transaksi dan laporan

### Formulir Dinamis
- Formulir pembelian dan penjualan menggunakan repeater untuk input detail item
- Validasi real-time untuk field yang saling terkait
- Pemilihan dropdown dengan pencarian untuk referensi data

### Tabel dan Filter
- Tabel dengan kolom yang informatif dan dapat diurutkan
- Filter berdasarkan tanggal dan kategori untuk laporan
- Paginasi untuk menangani dataset besar

## Pengelolaan Riwayat

### Riwayat Pembelian
- Tercatat di tabel `riwayat_pembelians` saat pembelian dibuat
- Diperbarui saat pembelian diedit
- Dihapus saat pembelian dihapus

### Riwayat Penjualan
- Tercatat di tabel `riwayat_penjualans` saat penjualan dibuat
- Diperbarui saat penjualan diedit
- Dihapus saat penjualan dihapus

## Fitur Reporting

### Laporan Stok
- Menampilkan detail stok berdasarkan batch pembelian
- Menunjukkan tanggal pembelian, stok awal, dan sisa st
