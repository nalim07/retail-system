<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice Laporan Pembelian</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 30px;
        }

        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
        }

        .company-info {
            margin-top: 5px;
            font-size: 11px;
        }

        .invoice-info {
            text-align: right;
            font-size: 11px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table th,
        .table td {
            border: 1px solid #444;
            padding: 6px;
            text-align: left;
        }

        .table th {
            background-color: #f0f0f0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .section {
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <table width="100%">
                <tr>
                    <td>
                        <h1>LAPORAN PEMBELIAN</h1>
                        <div class="company-info">
                            {{ config('app.name') }}
                        </div>
                    </td>
                    <td class="invoice-info">
                        <strong>Tanggal Cetak:</strong> {{ \Carbon\Carbon::now()->format('d M Y') }}<br>
                        @if ($from || $to)
                            <p style="font-weight: bold; margin-bottom: 10px;">
                                Periode:
                                {{ $from ? \Carbon\Carbon::parse($from)->format('d M Y') : '-' }}
                                s/d
                                {{ $to ? \Carbon\Carbon::parse($to)->format('d M Y') : '-' }}
                            </p>
                        @else
                            <p style="font-weight: bold; margin-bottom: 10px;">
                                Periode: Semua tanggal
                            </p>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        @if ($data->isEmpty())
            <p>Tidak ada data pembelian untuk ditampilkan.</p>
        @else
            <div class="section">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Barang</th>
                            <th>Harga Beli</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total = 0; @endphp
                        @foreach ($data as $item)
                            @php
                                $subtotal = $item->jumlah_pembelian * $item->harga_beli;
                                $total += $subtotal;
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($item->tanggal_pembelian)->format('d-m-Y') }}</td>
                                <td>{{ $item->nama_barang }}</td>
                                <td>Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                                <td>{{ $item->jumlah_pembelian }}</td>
                                <td>{{ $item->satuan }}</td>
                                <td>Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="total"><strong>Total Pembelian: Rp {{ number_format($total, 0, ',', '.') }}</strong></p>
            </div>
        @endif
    </div>
</body>

</html>
