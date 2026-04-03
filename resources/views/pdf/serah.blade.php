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

        .ttd {
            margin-top: 50px;
            width: 100%;
        }

        .ttd td {
            border: none;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <img src="{{ public_path('assets/logo_indogrosir.png') }}" class="logo">

        <div class="title">
            <h2>INDOGROSIR GORONTALO</h2>
            <p>Form Serah Terima Barang</p>
        </div>
    </div>

    <div class="line"></div>

    <!-- INFO -->
    <p>
        Nomor: <strong>{{ $req->nomor_serah }}</strong><br>
        Nama: {{ $req->user->name }}<br>
        Divisi: {{ $req->user->division->nama_divisi }}<br>
        Tanggal: {{ date('d-m-Y') }}
    </p>

    <!-- TABLE -->
    <table>
        <tr>
            <th>No</th>
            <th>Barang</th>
            <th>Keterangan</th>
            <th>Qty</th>
        </tr>

        @foreach($req->details as $i => $d)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $d->barang->nama_barang }}</td>
            <td>{{ $d->keterangan  }}</td>
            <td>{{ $d->qty }}</td>
        </tr>
        @endforeach
    </table>

    <!-- TTD -->
    <table class="ttd">
        <tr>
            <td>
                Yang Menyerahkan<br><br><br><br><br>
                (__________________)
            </td>
            <td>
                Yang Menerima<br><br><br><br><br>
                (__________________)
            </td>
        </tr>
    </table>

</body>
</html>