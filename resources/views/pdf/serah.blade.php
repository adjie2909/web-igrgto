<h2>Form Serah Terima Barang</h2>

<p>
Nama: {{ $req->user->name }} <br>
Divisi: {{ $req->user->division->nama_divisi }} <br>
Tanggal: {{ now()->format('d-m-Y') }}
</p>
<p>
Nomor Permintaan: <strong>{{ $req->nomor_dokumen }}</strong><br>
Nomor Serah Terima: <strong>{{ $req->nomor_serah }}</strong>
</p>

<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <tr>
        <th>No</th>
        <th>Barang</th>
        <th>Qty</th>
    </tr>

    @foreach($req->details as $i => $d)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $d->barang->nama_barang ?? $d->keterangan }}</td>
        <td>{{ $d->qty }}</td>
    </tr>
    @endforeach
</table>

<br><br>

<table width="100%">
    <tr>
        <td align="center">
            Yang Menyerahkan (PGA)<br><br><br><br><br><br>
            ____________________
        </td>
        <td align="center">
            Yang Menerima<br><br><br><br><br><br>
            ____________________
        </td>
    </tr>
</table>