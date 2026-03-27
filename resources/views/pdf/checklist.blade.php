<h2>Checklist Permintaan Barang</h2>

<p>
Nama: {{ $req->user->name }} <br>
Divisi: {{ $req->user->division->nama_divisi }} <br>
Tanggal: {{ now()->format('d-m-Y') }}
</p>
<p>
Nomor Permintaan: <strong>{{ $req->nomor_dokumen }}</strong>
</p>

<table border="1" width="100%" cellspacing="0" cellpadding="5">
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