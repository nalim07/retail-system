<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Informasi Barang -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <x-heroicon-o-cube class="w-5 h-5 inline mr-2"/>
                Informasi Barang
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Barang</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $record->barang->nama_barang }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategori</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $record->barang->kategori->nama_kategori ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Satuan</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $record->satuan }}</dd>
                </div>
            </dl>
        </div>

        <!-- Informasi Pembelian -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <x-heroicon-o-shopping-bag class="w-5 h-5 inline mr-2"/>
                Informasi Pembelian
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Pembelian</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $record->pembelian->tgl_pembelian->format('d F Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jumlah Pembelian</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ number_format($record->jumlah_pembelian) }} {{ $record->satuan }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Harga Beli</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">Rp {{ number_format($record->harga_beli, 0, ',', '.') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Status FIFO -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <x-heroicon-o-arrow-right-circle class="w-5 h-5 inline mr-2"/>
            Status FIFO
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($record->sisa) }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Sisa Stok ({{ $record->satuan }})</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($record->jumlah_pembelian - $record->sisa) }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Sudah Terjual ({{ $record->satuan }})</div>
            </div>
            <div class="text-center">
                @php
                    $persentaseTerjual = $record->jumlah_pembelian > 0 ? (($record->jumlah_pembelian - $record->sisa) / $record->jumlah_pembelian) * 100 : 0;
                @endphp
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {{ number_format($persentaseTerjual, 1) }}%
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Persentase Terjual</div>
            </div>
        </div>
    </div>

    <!-- Visual FIFO Queue -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <x-heroicon-o-queue-list class="w-5 h-5 inline mr-2"/>
            Visualisasi FIFO Queue
        </h3>
        <div class="mb-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Tanggal: {{ $record->pembelian->tgl_pembelian->format('d F Y') }} | 
                Jumlah: {{ number_format($record->jumlah_pembelian) }} {{ $record->satuan }}
            </p>
        </div>
        
        @php
            $jumlahPembelian = (int) $record->jumlah_pembelian;
            $sisaStok = (int) $record->sisa;
            $terjual = $jumlahPembelian - $sisaStok;
            
            // Hitung urutan FIFO untuk batch ini
            $urutanFifoBatch = \App\Models\PembelianDetail::where('id_barang', $record->id_barang)
                ->where('sisa', '>', 0)
                ->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian')
                ->where('pembelian.tgl_pembelian', '<', $record->pembelian->tgl_pembelian)
                ->count() + 1;
        @endphp
        
        <div class="grid grid-cols-10 sm:grid-cols-15 md:grid-cols-20 lg:grid-cols-25 gap-1 mb-4">
            @for($i = 0; $i < $jumlahPembelian; $i++)
                @php
                    $sudahTerjual = $i < $terjual;
                @endphp
                <div class="relative group">
                    <div class="w-8 h-8 border-2 rounded flex items-center justify-center text-xs font-bold transition-all duration-200 hover:scale-110
                        {{ $sudahTerjual 
                            ? 'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/30 dark:border-red-600 dark:text-red-300' 
                            : 'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/30 dark:border-green-600 dark:text-green-300' 
                        }}">
                        {{ $urutanFifoBatch }}
                    </div>
                    <!-- Tooltip -->
                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                        {{ $sudahTerjual ? 'Sudah Terjual' : 'Tersedia' }}
                    </div>
                </div>
            @endfor
        </div>
        
        <!-- Legend -->
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-green-100 border-2 border-green-300 rounded dark:bg-green-900/30 dark:border-green-600"></div>
                <span class="text-gray-600 dark:text-gray-400">Tersedia ({{ $sisaStok }} {{ $record->satuan }})</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-red-100 border-2 border-red-300 rounded dark:bg-red-900/30 dark:border-red-600"></div>
                <span class="text-gray-600 dark:text-gray-400">Sudah Terjual ({{ $terjual }} {{ $record->satuan }})</span>
            </div>
        </div>
        
        <!-- Progress Summary -->
        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress Penjualan:</span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ number_format($persentaseTerjual, 1) }}% ({{ $terjual }}/{{ $jumlahPembelian }})
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-2 dark:bg-gray-600">
                <div class="bg-gradient-to-r from-green-400 to-blue-500 h-2 rounded-full transition-all duration-300"
                     style="width: {{ $persentaseTerjual }}%"></div>
            </div>
        </div>
    </div>

    <!-- Urutan FIFO -->
    @php
        $urutanFifo = \App\Models\PembelianDetail::where('id_barang', $record->id_barang)
            ->where('sisa', '>', 0)
            ->join('pembelian', 'pembelian.id', '=', 'pembelian_detail.id_pembelian')
            ->where('pembelian.tgl_pembelian', '<', $record->pembelian->tgl_pembelian)
            ->count() + 1;
    @endphp

    @if($record->sisa > 0)
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                    Urutan FIFO: #{{ $urutanFifo }}
                </h3>
                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                    @if($urutanFifo == 1)
                        🎯 Batch ini akan <strong>keluar pertama</strong> saat ada penjualan
                    @else
                        ⏳ Batch ini menunggu {{ $urutanFifo - 1 }} batch lainnya untuk dijual terlebih dahulu
                    @endif
                </p>
            </div>
            <div class="text-right">
                @if($urutanFifo == 1)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Prioritas Tinggi
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        Menunggu Giliran
                    </span>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="text-center">
            <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-400">
                ✅ Batch Sudah Habis
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Semua stok dari batch pembelian ini sudah terjual
            </p>
        </div>
    </div>
    @endif
</div>
