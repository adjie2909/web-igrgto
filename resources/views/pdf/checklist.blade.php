<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .header {
            display: flex;
            align-items: center;
        }

        .logo {
            width: 80px;
        }

        .title {
            text-align: center;
            flex: 1;
        }

        .title h2 {
            margin: 0;
        }

        .line {
            border-bottom: 2px solid black;
            margin: 10px 0 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 6px;
            text-align: center;
        }

        .info {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="header">
        <img src="{{ public_path('assets/logo_indogrosir.png') }}" class="logo">

        <div class="title">
            <h2>INDOGROSIR GORONTALO</h2>
            <p>Checklist Permintaan Barang</p>
        </div>
    </div>

    <div class="line"></div>

    <!-- INFO -->
    <div class="info">
        Nomor: <strong>{{ $req->nomor_dokumen }}</strong><br>
        Nama: {{ $req->user->name }}<br>
        Divisi: {{ $req->user->division->nama_divisi }}<br>
        Tanggal: {{ date('d-m-Y', strtotime($req->tanggal_request)) }} <br>
    </div>

    <!-- TABLE -->
    <table>
        <tr>
            <th>No</th>
            <th>Barang</th>
            <th>Keterangan</th>
            <th>Qty</th>
            <th>Estimasi Biaya</th>
            <th>Cek</th>
        </tr>

        @php $totalEstimasi = 0; @endphp

        @foreach($req->details as $i => $d)

            @php
                $stok = $d->barang->stok ?? 0;
                $harga = $d->barang->harga_estimasi ?? 0;
                $kurang = max(0, $d->qty - $stok);
                $estimasi = $kurang * $harga;

                $totalEstimasi += $estimasi;
            @endphp

            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $d->barang->nama_barang}}</td>
                <td>{{ $d->keterangan }}</td>
                <td>{{ $d->qty }}</td>

                <td>
                    Rp {{ number_format($estimasi, 0, ',', '.') }}
                </td>

                <td></td>
            </tr>

        @endforeach
    </table>
    <table style="width:100%; margin-top:15px;">
        <tr>
            <td style="text-align:right; font-weight:bold;">
                Total Estimasi:
            </td>
            <td style="width:200px; font-weight:bold;">
                Rp {{ number_format($totalEstimasi, 0, ',', '.') }}
            </td>
        </tr>
    </table>
</body>

</html>