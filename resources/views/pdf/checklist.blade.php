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

        table, th, td {
            border: 1px solid black;
        }

        th, td {
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
            <th>Qty</th>
            <th>Cek</th>
        </tr>

        @foreach($req->details as $i => $d)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $d->barang->nama_barang ?? $d->keterangan }}</td>
            <td>{{ $d->qty }}</td>
            <td></td>
        </tr>
        @endforeach
    </table>

</body>
</html>