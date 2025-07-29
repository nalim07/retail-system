<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice Laporan Penjualan</title>
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
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <table width="100%">
                <tr>
                    <td>
                        <h1>LAPORAN PENJUALAN</h1>
                        <div class="company-info">
                            {{ config('app.name') }}
                        </div>
                    </td>
                    <td class="invoice-info">
                        <strong>Tanggal Cetak:</strong> {{ \Carbon\Carbon::now()->format('d M Y') }}<br>
                        @if ($from || $to)
                            <p>
                                <strong>Periode:</strong>
                                {{ $from ? \Carbon\Carbon::parse($from)->format('d M Y') : '-' }}
                                s/d
                                {{ $to ? \Carbon\Carbon::parse($to)->format('d M Y') : '-' }}
                            </p>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        @foreach ($data as $namaPelanggan => $items)
            <h3>{{ $namaPelanggan }}</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Barang</th>
                        <th>Harga Jual</th>
                        <th>Jumlah</th>
                        <th>Satuan</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($item->penjualan->tgl_penjualan)->format('d-m-Y') }}</td>
                            <td>{{ $item->barang->nama_barang }}</td>
                            <td>Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                            <td>{{ $item->jumlah_penjualan }}</td>
                            <td>{{ $item->barang->satuan }}</td>
                            <td>Rp {{ number_format($item->jumlah_penjualan * $item->harga_jual, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <br>
        @endforeach

    </div>
</body>

</html>
