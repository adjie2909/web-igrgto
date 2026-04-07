<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 14mm 12mm 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; border: none; padding: 0; }
        .logo { width: 86px; margin-top: 2px; }
        .title-wrap { text-align: center; padding-right: 86px; }
        .title-wrap h1 { margin: 0; font-size: 14px; letter-spacing: 0.2px; }
        .title-wrap p { margin: 4px 0 0; font-size: 9px; }
        .line { border-bottom: 2px solid #111; margin: 10px 0 12px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta-table td { border: none; padding: 2px 0; vertical-align: top; }
        .meta-label { width: 110px; }
        .meta-colon { width: 10px; }
        .items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items-table th,
        .items-table td { border: 1px solid #111; padding: 3px 4px; vertical-align: top; white-space: normal; word-break: break-word; }
        .items-table th { text-align: center; font-weight: 700; background: #f1f5f9; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .nowrap { white-space: nowrap; }
        .total-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .total-table td { border: 1px solid #111; padding: 5px 6px; font-weight: 700; }
        .ttd-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        .ttd-table td { width: 33.3333%; border: 1px solid #111; text-align: center; padding: 10px 8px; vertical-align: top; }
        .ttd-role { font-weight: 700; margin-bottom: 48px; }
        .ttd-line { display: inline-block; width: 180px; border-bottom: 1px solid #111; margin-bottom: 3px; }
        .small-note { margin-top: 6px; font-size: 8px; color: #374151; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 90px;"><img src="{{ public_path('assets/logo_indogrosir.png') }}" class="logo" alt="logo"></td>
            <td class="title-wrap">
                <h1>INDOGROSIR GORONTALO</h1>
                <p>Bukti Approval Permintaan Barang</p>
            </td>
        </tr>
    </table>

    <div class="line"></div>

    <table class="meta-table">
        <tr><td class="meta-label">Tanggal Cetak</td><td class="meta-colon">:</td><td>{{ $printedAt->format('d-m-Y H:i') }}</td></tr>
        <tr><td class="meta-label">Total Dokumen</td><td class="meta-colon">:</td><td>{{ $requests->count() }}</td></tr>
        <tr><td class="meta-label">Disetujui SM</td><td class="meta-colon">:</td><td>{{ $smName }}</td></tr>
        <tr><td class="meta-label">Disetujui SAM</td><td class="meta-colon">:</td><td>{{ $samName }}</td></tr>
    </table>

    @php $no = 1; $totalEstimasi = 0; @endphp
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 18%;">NoDoc</th>
                <th style="width: 14%;">User</th>
                <th style="width: 10%;">Divisi</th>
                <th style="width: 15%;">Barang</th>
                <th style="width: 13%;">Keterangan</th>
                <th style="width: 5%;">Qty</th>
                <th style="width: 10%;">Harga Satuan</th>
                <th style="width: 11%;">Estimasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $req)
                @foreach($req->details as $detail)
                    @php
                        $info = $detailStockInfo[$detail->id] ?? null;
                        $hargaSatuan = (int) ($info['harga_satuan'] ?? ($detail->harga_manual ?? ($detail->barang->harga_estimasi ?? 0)));
                        $estimasi = (int) ($info['estimasi'] ?? (((int) $detail->qty) * $hargaSatuan));
                        $totalEstimasi += $estimasi;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td>{{ $req->nomor_dokumen }}</td>
                        <td>{{ $req->user->name ?? '-' }}</td>
                        <td>{{ $req->user->division->nama_divisi ?? '-' }}</td>
                        <td>{{ $detail->barang->nama_barang ?? '-' }}</td>
                        <td>{{ $detail->keterangan ?: '-' }}</td>
                        <td class="text-center nowrap">{{ $detail->qty }}</td>
                        <td class="text-right nowrap">Rp {{ number_format($hargaSatuan, 0, ',', '.') }}</td>
                        <td class="text-right nowrap">Rp {{ number_format($estimasi, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
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
                <div class="ttd-role">SM</div>
                <span class="ttd-line"></span><br>
                {{ $smName }}
            </td>
            <td>
                <div class="ttd-role">SAM</div>
                <span class="ttd-line"></span><br>
                {{ $samName }}
            </td>
            <td>
                <div class="ttd-role">PGA</div>
                <span class="ttd-line"></span><br>
                PGA
            </td>
        </tr>
    </table>

    <div class="small-note">Dokumen ini dicetak otomatis saat approval final oleh SM.</div>
</body>
</html>
