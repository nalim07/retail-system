<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Barang;
use Filament\Forms\Form;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use Filament\Tables\Table;
use App\Models\PembelianDetail;
use App\Models\PenjualanDetail;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\PenjualanResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PenjualanResource\RelationManagers;

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Penjualan';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('tgl_penjualan')
                    ->label('Tanggal Penjualan')
                    ->default(now())
                    ->required(),

                Select::make('id_pelanggan')
                    ->label('Pelanggan')
                    ->options(Pelanggan::pluck('nama_pelanggan', 'id'))
                    ->searchable()
                    ->required(),

                Forms\Components\Repeater::make('penjualanDetails')
                    ->label('Daftar Barang')
                    ->relationship('penjualanDetails')
                    ->schema([
                        Forms\Components\Select::make('id_barang')
                            ->label('Barang')
                            ->options(\App\Models\Barang::pluck('nama_barang', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $barang = \App\Models\Barang::find($state);
                                $set('harga_jual', $barang?->harga_barang ?? 0);
                            }),
                        Forms\Components\TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->prefix('Rp')
                            ->readOnly(),
                        Forms\Components\TextInput::make('jumlah_penjualan')
                            ->label('Jumlah')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Penjualan::with(['penjualanDetails.barang', 'pelanggan'])
            )
            ->columns([
                TextColumn::make('tgl_penjualan')
                    ->label('Tanggal')
                    ->dateTime('d M Y'),

                TextColumn::make('pelanggan.nama_pelanggan')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('penjualanDetails.barang.nama_barang')
                    ->label('Barang')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->penjualanDetails->pluck('barang.nama_barang')->join(', ');
                    }),


                TextColumn::make('jumlah_total')
                    ->label('Jumlah')
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjualans::route('/'),
            'create' => Pages\CreatePenjualan::route('/create'),
            'edit' => Pages\EditPenjualan::route('/{record}/edit'),
        ];
    }

    public static function afterCreate(Form $form, $penjualan): void
    {
        DB::transaction(function () use ($penjualan) {
            // Salin detail penjualan yang di-input dari form ke array sementara
            // Ini diperlukan karena Filament mungkin sudah membuat PenjualanDetail sementara
            $inputDetails = $penjualan->penjualanDetails->toArray();

            // Hapus detail penjualan yang mungkin otomatis dibuat oleh Filament
            // Kita akan membuat ulang detail penjualan dengan mengacu pada batch FIFO
            $penjualan->penjualanDetails()->delete();

            // Iterasi setiap barang dalam penjualan
            foreach ($inputDetails as $detail) {
                $idBarang = $detail['id_barang'];
                $totalJumlah = $detail['jumlah_penjualan'];
                $hargaJual = $detail['harga_jual'];

                // Temukan barang berdasarkan ID
                $barang = Barang::find($idBarang);
                if (!$barang) {
                    throw new \Exception("Barang tidak ditemukan dengan ID: {$idBarang}");
                }

                // --- VALIDASI STOK GLOBAL SEBELUM PENGURANGAN ---
                // Pastikan stok total barang cukup sebelum mencoba mengurangi
                if ($barang->stok < $totalJumlah) {
                    throw new \Exception("Stok tidak cukup untuk barang: {$barang->nama_barang}. Stok tersedia: {$barang->stok}, Dibutuhkan: {$totalJumlah}");
                }

                // --- PENGURANGAN STOK GLOBAL ---
                // Kurangi stok total barang di tabel 'Barang'
                $barang->stok -= $totalJumlah;
                $barang->save(); // Simpan perubahan stok global

                // --- ALOKASI STOK MENGGUNAKAN METODE FIFO ---
                $remaining = $totalJumlah; // Jumlah yang masih perlu dialokasikan dari batch

                // Ambil semua detail pembelian (batch) untuk barang ini
                // Urutkan berdasarkan tanggal pembuatan (FIFO: yang pertama masuk, pertama keluar)
                // Hanya ambil batch yang masih memiliki sisa stok (> 0)
                $batches = PembelianDetail::where('id_barang', $idBarang)
                    ->where('sisa', '>', 0)
                    ->orderBy('created_at', 'asc') // Urutkan secara ascending (dari yang paling lama)
                    ->get();

                // Iterasi melalui setiap batch pembelian yang tersedia
                foreach ($batches as $batch) {
                    // Jika semua jumlah penjualan sudah terpenuhi, keluar dari loop batch
                    if ($remaining <= 0) {
                        break;
                    }

                    // Tentukan berapa banyak stok yang bisa diambil dari batch saat ini
                    // Ambil yang lebih kecil antara sisa kebutuhan ($remaining) atau stok yang tersedia di batch ($batch->sisa)
                    $take = min($remaining, $batch->sisa);

                    // --- PENGURANGAN STOK PER BATCH ---
                    // Kurangi jumlah sisa stok pada batch pembelian ini
                    $batch->sisa -= $take;
                    $batch->save(); // Simpan perubahan pada sisa stok batch

                    // --- BUAT DETAIL PENJUALAN UNTUK BATCH INI ---
                    // Catat bahwa sebagian (atau seluruh) barang yang terjual diambil dari batch pembelian ini
                    PenjualanDetail::create([
                        'id_penjualan' => $penjualan->id,
                        'id_barang' => $idBarang,
                        'jumlah_penjualan' => $take, // Jumlah yang diambil dari batch ini
                        'harga_jual' => $hargaJual,
                        'id_pembelian_detail' => $batch->id, // Tautkan ke ID detail pembelian (batch)
                    ]);

                    // Kurangi jumlah yang masih perlu dialokasikan
                    $remaining -= $take;
                }

                // --- VALIDASI KONSISTENSI STOK BATCH ---
                // Setelah semua batch diproses, pastikan tidak ada sisa kebutuhan yang belum terpenuhi
                if ($remaining > 0) {
                    // Ini berarti ada kekurangan stok di batch, padahal stok global sudah dicek
                    // Kemungkinan data sisa stok di PembelianDetail tidak konsisten dengan stok global
                    throw new \Exception("Kesalahan alokasi stok FIFO untuk barang: {$barang->nama_barang}. Masih ada {$remaining} unit yang belum dialokasikan dari batch.");
                }
            }
        });
    }
}
