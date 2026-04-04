@extends('layouts.app')

@section('content')

{{-- ========================= --}}
{{-- HEADER --}}
{{-- ========================= --}}
<div style="margin-bottom:15px;">
    <h2>Dashboard Ticketing Complain</h2>

    <div style="display:flex; gap:30px; font-size:28px; color:#475569;">
        <div><b>Nama:</b> {{ auth()->user()->name }}</div>
        <div><b>Divisi:</b> {{ auth()->user()->division->nama_divisi ?? '-' }}</div>
        <div><b>Role:</b> {{ auth()->user()->role }}</div>
    </div>
</div>

{{-- ========================= --}}
{{-- SUMMARY --}}
{{-- ========================= --}}
<div style="display:flex; gap:15px; margin-bottom:20px;">
    <div class="stat-card bg-total">
        <div class="stat-title">Total Ticket</div>
        <div class="stat-value">{{ $total }}</div>
    </div>

    <div class="stat-card bg-pending">
        <div class="stat-title">Total Open</div>
        <div class="stat-value">{{ $open }}</div>
    </div>

    <div class="stat-card bg-proses">
        <div class="stat-title">Total Dalam Pengecekan</div>
        <div class="stat-value">{{ $proses }}</div>
    </div>

    <div class="stat-card bg-selesai">
        <div class="stat-title">Selesai</div>
        <div class="stat-value">{{ $selesai }}</div>
    </div>
</div>

{{-- ========================= --}}
{{-- BUTTON --}}
{{-- ========================= --}}
@if(!in_array($divisionId, [9,10,14]))
    <a href="{{ route('ticket.create') }}" class="btn btn-blue">
        + Buat Ticket
    </a>
@endif

{{-- ========================= --}}
{{-- LIST --}}
{{-- ========================= --}}
<div class="card" style="margin-top:25px;">
    <h4>Ticket Terbaru</h4>

    <table>
        <thead>
            <tr>
                <th>Judul</th>
                <th>Handler</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Detail</th>
            </tr>
        </thead>

        <tbody>

        {{-- ========================= --}}
        {{-- DIVISI KHUSUS (GROUP) --}}
        {{-- ========================= --}}
        @if(in_array($divisionId, [9,10,14]))

            @foreach($groupedTickets ?? [] as $divisi => $list)

                {{-- HEADER DIVISI --}}
                <tr style="background:#f1f5f9;">
                    <td colspan="5">
                        <b>Divisi: {{ $divisi }}</b>
                    </td>
                </tr>

                @foreach($list->take(5) as $t)
                <tr>
                    <td>{{ $t->judul }}</td>

                    <td>
                        {{ $t->level == 1 ? 'EDP' : 'PGA' }}
                    </td>

                    <td>
                        @if($t->status == 0)
                            <span class="status-approved"">Open</span>
                        @elseif($t->status == 1)
                            <span class="status-proses">Diproses</span>
                        @else
                            <span class="status-selesai">Selesai</span>
                        @endif
                    </td>

                    <td>{{ $t->created_at->format('d-m-Y') }}</td>

                    <td>
                        <a href="{{ route('ticket.show', $t->id) }}" class="btn btn-outline">
                            Detail
                        </a>
                    </td>
                </tr>
                @endforeach

            @endforeach

        {{-- ========================= --}}
        {{-- DIVISI BIASA --}}
        {{-- ========================= --}}
        @else

            @foreach($tickets->take(5) as $t)
            <tr>
                <td>{{ $t->judul }}</td>

                <td>
                    {{ $t->level == 1 ? 'EDP' : 'PGA' }}
                </td>

                <td>
                    @if($t->status == 0)
                        <span class="status-approved">Open</span>
                    @elseif($t->status == 1)
                        <span class="status-proses">Diproses</span>
                    @else
                        <span class="status-selesai">Selesai</span>
                    @endif
                </td>

                <td>{{ $t->created_at->format('d-m-Y') }}</td>

                <td>
                    <a href="{{ route('ticket.show', $t->id) }}" class="btn btn-outline">
                        Detail
                    </a>
                </td>
            </tr>
            @endforeach

        @endif

        </tbody>
    </table>

</div>

@endsection