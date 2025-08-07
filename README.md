# Sistem Retail dengan Metode FIFO

<p align="center"><img src="public/img/logo-toko.png" width="200" alt="Logo Toko"></p>

## Tentang Sistem

Sistem Retail ini adalah aplikasi manajemen toko yang menggunakan metode FIFO (First In, First Out) untuk pengelolaan stok barang. Sistem ini dibangun menggunakan framework Laravel dan Filament untuk antarmuka admin yang modern dan responsif.

## Fitur Utama

### 1. Master Data
- **Pelanggan**: Pengelolaan data pelanggan termasuk nama, alamat, dan informasi kontak.
- **Kategori Barang**: Pengelompokan barang berdasarkan kategori untuk memudahkan pengelolaan.
- **Barang**: Pengelolaan data barang termasuk nama, stok, harga jual, dan kategori.

### 2. Transaksi
- **Pembelian (Restok)**: Mencatat transaksi pembelian atau restok barang dari supplier.
- **Penjualan**: Mencatat transaksi penjualan kepada pelanggan dengan implementasi metode FIFO.

### 3. Laporan
- **Laporan Stok**: Menampilkan informasi stok barang terkini.
- **Laporan Penjualan**: Menampilkan riwayat dan analisis penjualan.
- **Laporan Pembelian**: Menampilkan riwayat dan analisis pembelian.

## Metode FIFO (First In, First Out)

Sistem ini mengimplementasikan metode FIFO dalam pengelolaan stok barang, yang berarti:

1. Barang yang pertama kali dibeli (masuk) akan menjadi barang yang pertama kali dijual (keluar).
2. Ketika transaksi penjualan terjadi, sistem akan otomatis mengambil stok barang dari pembelian yang paling lama.
3. Hal ini memastikan perputaran stok yang baik dan mencegah barang menjadi kadaluarsa atau usang.

## Alur Kerja Sistem

### Alur Pembelian (Restok)
1. Admin mencatat transaksi pembelian barang dari supplier.
2. Sistem menyimpan detail pembelian termasuk jumlah, harga beli, dan tanggal pembelian.
3. Stok barang secara otomatis bertambah sesuai dengan jumlah pembelian.
4. Sistem menyimpan sisa stok dari pembelian ini untuk digunakan dalam metode FIFO.

### Alur Penjualan
1. Admin mencatat transaksi penjualan barang kepada pelanggan.
2. Sistem secara otomatis mengambil stok barang dari pembelian yang paling lama (FIFO).
3. Jika stok dari pembelian pertama habis, sistem akan mengambil dari pembelian berikutnya.
4. Stok barang secara otomatis berkurang sesuai dengan jumlah penjualan.
5. Sistem mencatat detail penjualan termasuk barang, jumlah, harga jual, dan pelanggan.

## Instalasi

### Persyaratan Sistem
- PHP >= 8.1
- Composer
- MySQL atau MariaDB
- Node.js & NPM

### Langkah Instalasi
1. Clone repositori ini
   ```
   git clone [url-repositori]
   ```

2. Instal dependensi PHP
   ```
   composer install
   ```

3. Instal dependensi JavaScript
   ```
   npm install
   ```

4. Salin file .env.example menjadi .env dan sesuaikan konfigurasi database
   ```
   cp .env.example .env
   ```

5. Generate application key
   ```
   php artisan key:generate
   ```

6. Jalankan migrasi dan seeder
   ```
   php artisan migrate --seed
   ```

7. Compile asset
   ```
   npm run dev
   ```

8. Jalankan server
   ```
   php artisan serve
   ```

## Akses Sistem

### Login Admin
- URL: `/admin/login`
- Email: admin@gmail.com
- Password: admin

## Struktur Database

### Tabel Utama
- **barang**: Menyimpan data master barang
- **kategori_barang**: Menyimpan data kategori barang
- **pelanggan**: Menyimpan data pelanggan
- **pembelian**: Menyimpan data header transaksi pembelian
- **pembelian_detail**: Menyimpan detail transaksi pembelian dan sisa stok untuk FIFO
- **penjualan**: Menyimpan data header transaksi penjualan
- **penjualan_detail**: Menyimpan detail transaksi penjualan dengan referensi ke pembelian_detail untuk FIFO
- **riwayat_pembelians**: Menyimpan riwayat pembelian untuk laporan
- **riwayat_penjualans**: Menyimpan riwayat penjualan untuk laporan

## Pengembangan

Sistem ini dikembangkan menggunakan:
- Laravel 11.x - Framework PHP
- Filament 3.x - Admin Panel
- MySQL/MariaDB - Database

## Lisensi

Sistem ini dilisensikan di bawah [MIT license](https://opensource.org/licenses/MIT).
