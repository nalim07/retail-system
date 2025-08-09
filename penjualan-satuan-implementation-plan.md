# Rencana Implementasi Penambahan Field "Satuan" pada Penjualan

## Gambaran Umum
Dokumen ini menjelaskan langkah-langkah yang diperlukan untuk menambahkan field "satuan" pada form penjualan (PenjualanResource) di sistem retail dengan metode FIFO.

## Perubahan yang Diperlukan

### 1. Modifikasi Form Schema di PenjualanResource

File yang perlu diubah: `app/Filament/Resources/PenjualanResource.php`

Dalam method `form()`, perlu menambahkan field "satuan" pada schema repeater `penjualanDetails`.

#### Perubahan Saat Ini:
```php
Forms\Components\Repeater::make('penjualanDetails')
    ->label('Daftar Barang')
    ->relationship('penjualanDetails')
    ->schema([
        Select::make('id_barang')
            ->label('Barang')
            ->options(\App\Models\Barang::pluck('nama_barang', 'id'))
            ->searchable()
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set) {
                $barang = \App\Models\Barang::find($state);
                $set('harga_jual', $barang?->harga_jual ?? 0);
            }),
        TextInput::make('harga_jual')
            ->label('Harga Jual')
            ->prefix('Rp')
            ->readOnly(),
        TextInput::make('jumlah_penjualan')
            ->label('Jumlah')
            ->numeric()
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                $idBarang = $get('id_barang');
                
                if (!$idBarang || !$state) return;
                
                $totalSisa = \App\Models\PembelianDetail::where('id_barang', $idBarang)
                    ->where('sisa', '>', 0)
                    ->sum('sisa');
                
                if ($state > $totalSisa) {
                    Notification::make()
                        ->title('Stok tidak mencukupi')
                        ->body("Stok barang hanya tersedia {$totalSisa}, permintaan {$state}.")
                        ->danger()
                        ->send();
                    
                    $set('jumlah_penjualan', null); // reset input jika invalid
                }
            }),
    ])
```

#### Perubahan yang Diperlukan:
```php
Forms\Components\Repeater::make('penjualanDetails')
    ->label('Daftar Barang')
    ->relationship('penjualanDetails')
    ->schema([
        Select::make('id_barang')
            ->label('Barang')
            ->options(\App\Models\Barang::pluck('nama_barang', 'id'))
            ->searchable()
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set) {
                $barang = \App\Models\Barang::find($state);
                $set('harga_jual', $barang?->harga_jual ?? 0);
                // Tambahkan ini untuk mengisi satuan secara otomatis
                if ($barang) {
                    $set('satuan', $barang->satuan);
                }
            }),
        TextInput::make('harga_jual')
            ->label('Harga Jual')
            ->prefix('Rp')
            ->readOnly(),
        TextInput::make('jumlah_penjualan')
            ->label('Jumlah')
            ->numeric()
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                $idBarang = $get('id_barang');
                
                if (!$idBarang || !$state) return;
                
                $totalSisa = \App\Models\PembelianDetail::where('id_barang', $idBarang)
                    ->where('sisa', '>', 0)
                    ->sum('sisa');
                
                if ($state > $totalSisa) {
                    Notification::make()
                        ->title('Stok tidak mencukupi')
                        ->body("Stok barang hanya tersedia {$totalSisa}, permintaan {$state}.")
                        ->danger()
                        ->send();
                    
                    $set('jumlah_penjualan', null); // reset input jika invalid
                }
            }),
        // Tambahkan field satuan di sini
        TextInput::make('satuan')
            ->required()
            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Satuan terisi otomatis sesuai dengan satuan yang ada di tabel barang')
            ->placeholder('contoh: pcs, kg')
            ->reactive(),
    ])
```

### 2. Validasi dan Pengujian

Setelah perubahan diimplementasikan, perlu dilakukan pengujian untuk memastikan:
1. Field "satuan" muncul dengan benar di form penjualan
2. Field "satuan" terisi otomatis ketika memilih barang
3. Data "satuan" tersimpan dengan benar di database
4. Tidak ada error atau masalah dengan fungsionalitas yang ada

## Struktur Database

Berdasarkan analisis migrasi database:
- Tabel `penjualan_detail` sudah memiliki kolom `satuan` yang nullable
- Tabel `barang` memiliki kolom `satuan` yang required

## Rekomendasi Implementasi

Untuk mengimplementasikan perubahan ini, disarankan untuk:
1. Membuat backup database sebelum melakukan perubahan
2. Mengimplementasikan perubahan dalam lingkungan development terlebih dahulu
3. Melakukan pengujian menyeluruh sebelum deploy ke production
4. Memastikan tim pengguna diinformasikan tentang perubahan ini

## Potensi Masalah dan Solusi

### 1. Field "satuan" tidak terisi otomatis
**Masalah**: Field "satuan" tetap kosong meskipun barang sudah dipilih.
**Solusi**: 
- Pastikan relasi antara PenjualanDetail dan Barang sudah benar
- Periksa apakah field "satuan" di tabel barang memiliki nilai
- Verifikasi bahwa method `afterStateUpdated` di field `id_barang` sudah benar

### 2. Validasi field "satuan" terlalu ketat
**Masalah**: Pengguna tidak bisa mengubah satuan meskipun dalam kasus tertentu diperlukan.
**Solusi**:
- Pertimbangkan untuk membuat field "satuan" tidak wajib (nullable) jika memang diperlukan fleksibilitas
- Tambahkan logika bisnis untuk kasus-kasus khusus

### 3. Inkompatibilitas dengan data lama
**Masalah**: Data penjualan yang sudah ada mungkin tidak memiliki nilai "satuan".
**Solusi**:
- Jalankan migrasi untuk mengisi nilai "satuan" berdasarkan data barang terkait
- Tambahkan penanganan khusus untuk data lama yang tidak memiliki "satuan"

### 4. Masalah tampilan di tabel
**Masalah**: Kolom "satuan" mungkin perlu ditambahkan di tabel daftar penjualan.
**Solusi**:
- Pertimbangkan menambahkan kolom "satuan" di method `table()` jika diperlukan
- Sesuaikan tampilan tabel untuk menampilkan informasi satuan

## Kesimpulan

Penambahan field "satuan" pada form penjualan akan meningkatkan kelengkapan informasi transaksi dan konsistensi data dengan form pembelian. Implementasi ini relatif sederhana karena:
1. Struktur database sudah mendukung
2. Pendekatan serupa sudah diimplementasikan di form pembelian
3. Hanya perlu menambahkan field dan logika pengisian otomatis

Dengan mengikuti rencana implementasi ini, sistem akan menjadi lebih konsisten dan informatif untuk pengguna.
