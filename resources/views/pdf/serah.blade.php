<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22mm 16mm 20mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { border: none; padding: 0; vertical-align: top; }
        .logo { width: 86px; margin-top: 2px; }
        .title-wrap { text-align: center; padding-right: 86px; }
        .title-wrap h1 { margin: 0; font-size: 22px; letter-spacing: .3px; }
        .title-wrap p { margin: 6px 0 0; font-size: 13px; }
        .line { border-bottom: 2px solid #111; margin: 10px 0 12px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta-table td { border: none; padding: 2px 0; vertical-align: top; }
        .meta-label { width: 110px; }
        .meta-colon { width: 10px; }
        .items-table { width: 100%; border-collapse: collapse; table-layout: auto; }
        .items-table th, .items-table td { border: 1px solid #111; padding: 5px 6px; vertical-align: top; }
        .items-table th { text-align: center; font-weight: 700; background: #f1f5f9; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .nowrap { white-space: nowrap; }
        .total-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .total-table td { border: 1px solid #111; padding: 7px 8px; font-weight: 700; }
        .ttd-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        .ttd-table td { width: 50%; border: 1px solid #111; text-align: center; padding: 10px 8px; vertical-align: top; }
        .ttd-role { font-weight: 700; margin-bottom: 58px; }
        .ttd-line { display: inline-block; width: 180px; border-bottom: 1px solid #111; margin-bottom: 3px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 90px;"><img src="{{ public_path('assets/logo_indogrosir.png') }}" class="logo" alt="logo"></td>
            <td class="title-wrap">
                <h1>INDOGROSIR GORONTALO</h1>
                <p>Form Serah Terima Barang</p>
            </td>
        </tr>
    </table>

    <div class="line"></div>

    <table class="meta-table">
        <tr><td class="meta-label">Nomor</td><td class="meta-colon">:</td><td><strong>{{ $req->nomor_serah }}</strong></td></tr>
        <tr><td class="meta-label">Nama</td><td class="meta-colon">:</td><td>{{ $req->user->name }}</td></tr>
        <tr><td class="meta-label">Divisi</td><td class="meta-colon">:</td><td>{{ $req->user->division->nama_divisi ?? '-' }}</td></tr>
        <tr><td class="meta-label">Tanggal</td><td class="meta-colon">:</td><td>{{ date('d-m-Y') }}</td></tr>
    </table>

    @php $totalEstimasi = 0; @endphp
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 27%;">Barang</th>
                <th style="width: 30%;">Keterangan</th>
                <th style="width: 8%;">Qty</th>
                <th style="width: 14%;">Harga Satuan</th>
                <th style="width: 16%;">Estimasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($req->details as $i => $d)
                @php
                    $info = $detailStockInfo[$d->id] ?? null;
                    $hargaSatuan = (int) ($info['harga_satuan'] ?? ($d->harga_manual ?? ($d->barang->harga_estimasi ?? 0)));
                    $estimasi = (int) ($info['estimasi'] ?? (((int) $d->qty) * $hargaSatuan));
                    $totalEstimasi += $estimasi;
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $d->barang->nama_barang ?? '-' }}</td>
                    <td>{{ $d->keterangan ?: '-' }}</td>
                    <td class="text-center nowrap">{{ $d->qty }}</td>
                    <td class="text-right nowrap">Rp {{ number_format($hargaSatuan, 0, ',', '.') }}</td>
                    <td class="text-right nowrap">Rp {{ number_format($estimasi, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="total-table">
        <tr>
            <td class="text-right">Total Estimasi</td>
            <td style="width: 180px;" class="text-right">Rp {{ number_format($totalEstimasi, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="ttd-table">
        <tr>
            <td>
                <div class="ttd-role">Yang Menyerahkan</div>
                <span class="ttd-line"></span><br>
                PGA
            </td>
            <td>
                <div class="ttd-role">Yang Menerima</div>
                <span class="ttd-line"></span><br>
                {{ $req->user->name }}
            </td>
        </tr>
    </table>
</body>
</html>
