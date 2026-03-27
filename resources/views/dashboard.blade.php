@extends('layouts.app')

@section('content')


<div class="card">
    <h1>Dashboard Permintaan Barang GA</h1>

    <div style="display:flex; gap:30px; font-size:28px; color:#475569;">
        <div><b>Nama:</b> {{ auth()->user()->name }}</div>
        <div><b>Divisi:</b> {{ auth()->user()->division->nama_divisi ?? '-' }}</div>
        <div><b>Role:</b> {{ auth()->user()->role }}</div>
    </div>
</div>


<div style="display:flex; gap:12px; flex-wrap:wrap;">

    <div class="stat-card bg-total">
        <div class="stat-title">Total Request</div>
        <div class="stat-value">{{ $total }}</div>
    </div>

    <div class="stat-card bg-pending">
        <div class="stat-title">Pending</div>
        <div class="stat-value">{{ $pending }}</div>
    </div>

    <div class="stat-card bg-approved">
        <div class="stat-title">Approved</div>
        <div class="stat-value">{{ $approved }}</div>
    </div>

    <div class="stat-card bg-proses">
        <div class="stat-title">Diproses</div>
        <div class="stat-value">{{ $diproses }}</div>
    </div>

    <div class="stat-card bg-tolak">
        <div class="stat-title">Ditolak</div>
        <div class="stat-value">{{ $ditolak }}</div>
    </div>

    <div class="stat-card bg-selesai">
        <div class="stat-title">Selesai</div>
        <div class="stat-value">{{ $selesai }}</div>
    </div>

</div>

<div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding: 20px;">


    <div style="display:flex; gap:10px;">

        <a href="/request" class="btn btn-outline">
            Lihat Request
        </a>

        @if(auth()->user()->role == 'USER')
            <a href="/request/create" class="btn btn-primary">
                + Buat Request
            </a>
        @endif

    </div>

</div>

<div class="card">
    <h3>Request Terbaru</h3>

    <table>
        <tr>
            <th>Nodoc</th>
            <th>Divisi</th>
            <th>Status</th>
            <th>Tanggal</th>
        </tr>

        @foreach($recentRequests as $r)
        <tr>
            <td>{{ $r->nomor_dokumen }}</td>
            <td>{{$r->user->division->nama_divisi}}</td>
            <td>
                @if($r->status == 0)
                    <span class="status-pending">Pending</span>
                @elseif($r->status == 1)
                    <span class="status-approved">Approved</span>
                @elseif($r->status == 2)
                    <span class="status-proses">Diproses</span>
                @else
                    <span class="status-selesai">Selesai</span>
                @endif
            </td>
            <td>{{ $r->created_at->format('d-m-Y') }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endsection