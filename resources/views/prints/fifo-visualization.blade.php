<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan FIFO Visualization</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }

        .container {
            width: 100%;
            max-width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }

        .legend-color.first-batch {
            background-color: #10b981;
        }

        .legend-color.other-batch {
            background-color: #3b82f6;
        }

        .table-container {
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 20px;
        }

        .data-table th {
            background-color: #374151;
            color: white;
            padding: 8px 6px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #6b7280;
            font-size: 9px;
        }

        .data-table td {
            padding: 6px 4px;
            border: 1px solid #d1d5db;
            text-align: center;
            font-size: 9px;
            vertical-align: middle;
        }

        .data-table .text-left {
            text-align: left;
        }

        .first-batch-row {
            background-color: #ecfdf5;
        }

        .first-batch-row td {
            border-color: #10b981;
        }

        .other-batch-row {
            background-color: #eff6ff;
        }

        .other-batch-row td {
            border-color: #3b82f6;
        }

        .status-cell {
            text-align: center;
        }

        .status-priority {
            background-color: #10b981;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .status-waiting {
            background-color: #3b82f6;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .fifo-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .summary-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .summary-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .summary-grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .summary-item {
            flex: 1;
            min-width: 150px;
        }

        .summary-label {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }

        @media print {
            body {
                margin: 0;
                padding: 15px;
            }

            .fifo-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Laporan FIFO Visualization</h1>
            <p>Monitoring Stok Barang Berdasarkan Sistem FIFO (First In, First Out)</p>
            <p>Dicetak pada: {{ date('d/m/Y H:i:s') }}</p>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color first-batch"></div>
                <span>Batch Pertama (Prioritas Keluar)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color other-batch"></div>
                <span>Batch Selanjutnya</span>
            </div>
        </div>

        @php
            $groupedRecords = $records->groupBy('barang.nama_barang');
            $totalBarang = $groupedRecords->count();
            $totalBatch = $records->count();
            $totalStok = $records->sum('sisa');
            $totalNilai = $records->sum(function($record) {
                return $record->sisa * $record->harga_beli;
            });
        @endphp

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Tanggal Pembelian</th>
                        <th>Jumlah Pembelian</th>
                        <th>Satuan</th>
                        <th>Harga Beli</th>
                        {{-- <th>Total Nilai</th> --}}
                        <th>Status FIFO</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @foreach($groupedRecords as $namaBarang => $barangRecords)
                        @foreach($barangRecords as $index => $record)
                            <tr class="{{ $index === 0 ? 'first-batch-row' : 'other-batch-row' }}">
                                <td>{{ $no++ }}</td>
                                <td class="text-left">{{ $namaBarang }}</td>
                                <td class="text-left">{{ $record->barang->kategori->nama_kategori ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($record->pembelian->tgl_pembelian)->format('d/m/Y') }}</td>
                                <td>{{ number_format($record->sisa, 0, ',', '.') }}</td>
                                <td>{{ $record->barang->satuan ?? 'pcs' }}</td>
                                <td>Rp {{ number_format($record->harga_beli, 0, ',', '.') }}</td>
                                {{-- <td>Rp {{ number_format($record->sisa * $record->harga_beli, 0, ',', '.') }}</td> --}}
                                <td class="status-cell">
                                    @if($index === 0)
                                        <span class="status-priority">Prioritas Keluar</span>
                                    @else
                                        <span class="status-waiting">Menunggu</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary per Barang -->
        @foreach($groupedRecords as $namaBarang => $barangRecords)
            <div class="summary-section">
                <div class="summary-title">Ringkasan {{ $namaBarang }}</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Total Batch</div>
                        <div class="summary-value">{{ $barangRecords->count() }} batch</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Stok</div>
                        <div class="summary-value">{{ number_format($barangRecords->sum('sisa'), 0, ',', '.') }} {{ $barangRecords->first()->barang->satuan ?? 'pcs' }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Nilai</div>
                        <div class="summary-value">Rp {{ number_format($barangRecords->sum(function($r) { return $r->sisa * $r->harga_beli; }), 0, ',', '.') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Rata-rata Harga</div>
                        <div class="summary-value">Rp {{ number_format($barangRecords->avg('harga_beli'), 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="summary-section" style="margin-top: 40px;">
            <div class="summary-title">Ringkasan Keseluruhan</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Total Jenis Barang</div>
                    <div class="summary-value">{{ $totalBarang }} jenis</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Batch</div>
                    <div class="summary-value">{{ $totalBatch }} batch</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Stok</div>
                    <div class="summary-value">{{ number_format($totalStok, 0, ',', '.') }} unit</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Nilai Stok</div>
                    <div class="summary-value">Rp {{ number_format($totalNilai, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Laporan ini dibuat secara otomatis oleh sistem pada {{ date('d/m/Y H:i:s') }}</p>
            <p>Data menampilkan semua batch barang yang masih memiliki stok tersedia</p>
        </div>
    </div>
</body>
</html>
