<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice Laporan Stok</title>
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

        .footer {
            position: fixed;
            bottom: 30px;
            left: 30px;
            right: 30px;
            text-align: center;
            font-size: 10px;
            color: #777;
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
                        <h1>LAPORAN STOK</h1>
                        <div class="company-info">
                            {{ config('app.name') }}<br>
                            {{-- Jl. Contoh No. 123, Jakarta<br>
                            Telp: (021) 12345678 --}}
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

        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th class="text-right">Harga Beli</th>
                    <th class="text-right">Stok Awal</th>
                    <th class="text-right">Sisa</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->pembelian->tgl_pembelian)->format('d-m-Y') }}</td>
                        <td>{{ $item->barang->nama_barang }}</td>
                        <td>{{ $item->barang->kategori->nama_kategori ?? '-' }}</td>
                        <td>{{ $item->barang->jenis_barang }}</td>
                        <td class="text-right">Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                        <td class="text-right">{{ $item->jumlah_pembelian }}</td>
                        <td class="text-right">{{ $item->sisa }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="text-align: center;">
                            <em>Tidak ada data untuk periode ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <br><br>
        {{-- <table width="100%">
            <tr>
                <td class="text-center">
                    <br><br>
                    <strong>Mengetahui</strong><br>
                    Kepala Gudang<br><br><br><br>
                    (____________________)
                </td>
                <td class="text-center">
                    <br><br>
                    <strong>Dicetak Oleh</strong><br>
                    Admin<br><br><br><br>
                    (____________________)
                </td>
            </tr>
        </table> --}}
    </div>

    {{-- <div class="footer">
        Dicetak melalui sistem. Tidak memerlukan tanda tangan basah.
    </div> --}}
</body>

</html>
