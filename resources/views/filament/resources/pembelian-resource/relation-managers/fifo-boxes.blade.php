<div class="p-6">
    <div class="mb-6">
        <div class="mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Visualisasi Stok FIFO (First In, First Out)
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Setiap kotak mewakili batch pembelian. Angka menunjukkan urutan FIFO untuk setiap barang.
                </p>
            </div>
        </div>
    </div>

    @if ($records->isEmpty())
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak Ada Data Stok FIFO</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Belum ada data pembelian untuk ditampilkan dalam sistem FIFO.
            </p>
        </div>
    @else
        @php
            $groupedRecords = $records->groupBy('id_barang');
        @endphp

        @foreach ($groupedRecords as $barangId => $barangRecords)
            @php
                $firstRecord = $barangRecords->first();
            @endphp

            <div class="mb-8 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="mb-4">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white">
                        {{ $firstRecord->barang->nama_barang }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Total {{ $barangRecords->sum('sisa') }} unit tersisa dari {{ $barangRecords->count() }} batch
                        @if ($barangRecords->count() > 1)
                            <br>Tanggal pembelian:
                            {{ $barangRecords->pluck('pembelian.tgl_pembelian')->map(fn($date) => \Carbon\Carbon::parse($date)->format('d/m/Y'))->unique()->implode(', ') }}
                        @endif
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    @foreach ($barangRecords as $index => $record)
                        @php
                            // Hitung urutan FIFO untuk batch ini
                            $fifoOrder = $index + 1;

                            // Tentukan status berdasarkan urutan FIFO
                            $isFirstBatch = $fifoOrder === 1;
                            $boxColor = $isFirstBatch ? 'bg-green-500' : 'bg-blue-500';
                            $textColor = 'text-white';
                            $borderColor = $isFirstBatch ? 'border-green-600' : 'border-blue-600';
                        @endphp

                        <div class="flex flex-wrap gap-2">
                            @for ($i = 1; $i <= $record->sisa; $i++)
                                <div class="relative group">
                                    <div
                                        class="w-16 h-16 {{ $boxColor }} {{ $borderColor }} border-2 rounded-lg flex items-center justify-center {{ $textColor }} font-bold text-lg shadow-md hover:shadow-lg transition-all duration-200 cursor-pointer transform hover:scale-105">
                                        {{ $fifoOrder }}
                                    </div>

                                    <!-- Tooltip -->
                                    <div
                                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                                        <div class="font-semibold">{{ $record->barang->nama_barang }}</div>
                                        <div>Batch #{{ $fifoOrder }} - Unit
                                            {{ $i }}/{{ $record->sisa }}</div>
                                        <div>Tgl Beli:
                                            {{ \Carbon\Carbon::parse($record->pembelian->tgl_pembelian)->format('d/m/Y') }}
                                        </div>
                                        <div>Harga: Rp {{ number_format($record->harga_beli, 0, ',', '.') }}</div>
                                        <div>Status: {{ $isFirstBatch ? 'Prioritas Keluar' : 'Menunggu Antrian' }}
                                        </div>

                                        <!-- Tooltip arrow -->
                                        <div
                                            class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900">
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    @endforeach
                </div>

                <!-- Enhanced Summary Section -->
                <div
                    class="mt-4 p-5 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 shadow-sm">
                    <h6 class="text-base font-bold text-gray-900 dark:text-white my-4 flex items-center space-x-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Ringkasan Stok</span>
                    </h6>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div
                            class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Batch</div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ $barangRecords->count() }}</div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Stok</div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ $barangRecords->sum('sisa') }} <span class="text-sm font-normal">unit</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Batch Aktif</div>
                                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400">Batch #1</div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Harga Rata-rata
                                    </div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                                        Rp {{ number_format($barangRecords->avg('harga_beli'), 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Global Legend -->
        <section aria-labelledby="ket" class="mt-6">
            <div
                class="rounded-xl border border-gray-200 dark:border-gray-600 bg-white/70 dark:bg-gray-800/70 p-5 shadow-sm">
                <!-- Header -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                        </svg>
                    </div>
                    <div>
                        <h5 id="ket" class="font-semibold text-gray-900 dark:text-white leading-tight">
                            Keterangan</h5>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Panduan membaca visualisasi FIFO</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Enhanced Legend Section -->
                    <div class="space-y-6">
                        <h6 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Status Batch</h6>
                        <div class="space-y-4">
                            <div
                                class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="w-10 h-10 bg-gradient-to-r from-green-400 to-green-600 rounded-lg border-2 border-green-300 flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">1</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-green-800 dark:text-green-200 mb-1">Batch
                                            Prioritas</div>
                                        <div class="text-xs text-green-600 dark:text-green-300">Keluar pertama (FIFO)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="w-10 h-10 bg-gradient-to-r from-blue-400 to-blue-600 rounded-lg border-2 border-blue-300 flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">2+</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">Batch
                                            Menunggu</div>
                                        <div class="text-xs text-blue-600 dark:text-blue-300">Antrian berdasarkan
                                            tanggal</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Poin penjelasan -->
                    <div class="space-y-6">
                        <h6 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Panduan Penggunaan</h6>
                        <ul class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
                            <li class="flex gap-3">
                                <svg class="w-5 h-5 mt-0.5 flex-none text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6" />
                                </svg>
                                <span class="leading-relaxed">Angka di dalam kotak menunjukkan urutan batch
                                    FIFO.</span>
                            </li>
                            {{-- <li class="flex gap-3">
                                <svg class="w-5 h-5 mt-0.5 flex-none text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                </svg>
                                <span class="leading-relaxed">Arahkan kursor (hover) pada kotak untuk melihat detail
                                    lengkap.</span>
                            </li> --}}
                            <li class="flex gap-3">
                                <svg class="w-5 h-5 mt-0.5 flex-none text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="leading-relaxed">Sistem FIFO memastikan stok lama keluar terlebih
                                    dahulu.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>

<style>
    /* Custom animations */
    @keyframes pulse-green {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
        }

        50% {
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0);
        }
    }

    @keyframes pulse-blue {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
        }

        50% {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0);
        }
    }

    .bg-green-500:hover {
        animation: pulse-green 1.5s infinite;
    }

    .bg-blue-500:hover {
        animation: pulse-blue 1.5s infinite;
    }
</style>
