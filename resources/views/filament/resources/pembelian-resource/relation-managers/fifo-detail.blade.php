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

    <!-- Progress Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Progress Penjualan</h3>
        <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
            <div class="bg-gradient-to-r from-green-400 to-blue-500 h-4 rounded-full transition-all duration-300"
                 style="width: {{ $persentaseTerjual }}%"></div>
        </div>
        <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mt-2">
            <span>0</span>
            <span>{{ number_format($record->jumlah_pembelian) }} {{ $record->satuan }}</span>
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
