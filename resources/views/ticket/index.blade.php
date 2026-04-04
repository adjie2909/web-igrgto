@extends('layouts.app')

@section('content')

<div class="card">

    <h2>Ticketing Complain</h2>

    {{-- ========================= --}}
    {{-- DASHBOARD SUMMARY --}}
    {{-- ========================= --}}
    <div style="display:flex; gap:15px; margin-bottom:20px;">

        <div class="stat-box" style="background:#e0f2fe;">
            <div>Total</div>
            <h3>{{ $total }}</h3>
        </div>

        <div class="stat-box" style="background:#fef9c3;">
            <div>Open</div>
            <h3>{{ $open }}</h3>
        </div>

        <div class="stat-box" style="background:#dbeafe;">
            <div>Diproses</div>
            <h3>{{ $proses }}</h3>
        </div>

        <div class="stat-box" style="background:#dcfce7;">
            <div>Selesai</div>
            <h3>{{ $selesai }}</h3>
        </div>

    </div>

    {{-- ========================= --}}
    {{-- USER BUTTON --}}
    {{-- ========================= --}}
    @if(!in_array($divisionId, [9,10]))
        <a href="{{ route('ticket.create') }}" class="btn btn-blue">
            + Buat Ticket
        </a>
    @endif

    <br><br>

    <table>
        <tr>
            <th>Judul</th>
            <th>Status</th>
            <th>Handler</th>
            <th>Aksi</th>
        </tr>

        @foreach($tickets as $t)
        <tr>

            <td>{{ $t->judul }}</td>

            {{-- STATUS --}}
            <td>
                @if($t->status == 0)
                    <span class="badge badge-blue">Open</span>
                @elseif($t->status == 1)
                    <span class="badge badge-orange">Diproses</span>
                @else
                    <span class="badge badge-green">Selesai</span>
                @endif
            </td>

            {{-- HANDLER --}}
            <td>
                @if($t->level == 1)
                    EDP
                @else
                    PGA
                @endif
            </td>

            {{-- AKSI --}}
            <td>
                <a href="{{ route('ticket.show', $t->id) }}" class="btn btn-outline">
                    Detail
                </a>
            </td>

        </tr>
        @endforeach

    </table>

</div>

@endsection