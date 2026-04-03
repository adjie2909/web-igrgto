<h3>Notifikasi Sistem</h3>

<p>Nama: {{ $data->user->name ?? '-' }}</p>

@if($type == 'request')
    <p>Ini adalah permintaan barang baru.</p>
@endif

@if($type == 'ticket')
    <p>Ticket baru telah dibuat.</p>
@endif

<p>Silakan cek sistem untuk detail.</p>