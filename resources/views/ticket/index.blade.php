@extends('layouts.app')

@section('content')
<div class="page-stack">
    <x-public-hero
        {{--eyebrow="Support Center"--}}
        title="Dashboard Ticketing Complain"
        {{--subtitle="Pantau laporan terbaru, progres penanganan, dan akses cepat untuk membuat ticket baru."--}}
    />

    <section class="stats-grid">
        <div class="stat-card bg-total">
            <div class="stat-title">Total Ticket</div>
            <div class="stat-value">{{ $total }}</div>
        </div>
        <div class="stat-card bg-pending">
            <div class="stat-title">Total Open</div>
            <div class="stat-value">{{ $open }}</div>
        </div>
        <div class="stat-card bg-proses">
            <div class="stat-title">Dalam Pengecekan</div>
            <div class="stat-value">{{ $proses }}</div>
        </div>
        <div class="stat-card bg-selesai">
            <div class="stat-title">Selesai</div>
            <div class="stat-value">{{ $selesai }}</div>
        </div>
    </section>

    @if(!in_array($divisionId, [9,10,14]))
        <section class="card card--soft">
            <div class="section-actions">
                <a href="{{ route('ticket.create') }}" class="btn btn-primary">Buat Ticket</a>
            </div>
        </section>
    @endif

    <section class="card table-section">
        <div class="card-header">
            <div>
                <h3 class="card-title">List Ticket Case</h3>
                <p class="card-description">Daftar ticket yang paling baru dan perlu dipantau.</p>
            </div>
        </div>

        <div class="table-wrap">
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
                    @if(in_array($divisionId, [9,10,14]))
                        @foreach($groupedTickets ?? [] as $divisi => $list)
                            <tr class="group-row">
                                <td colspan="5">Divisi: {{ $divisi }}</td>
                            </tr>

                            @foreach($list->take(5) as $t)
                                <tr>
                                    <td>{{ $t->judul }}</td>
                                    <td>{{ $t->level == 1 ? 'EDP' : 'PGA' }}</td>
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
                                        <a href="{{ route('ticket.show', $t->id) }}" class="btn btn-outline">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @else
                        @foreach($tickets->take(5) as $t)
                            <tr>
                                <td>{{ $t->judul }}</td>
                                <td>{{ $t->level == 1 ? 'EDP' : 'PGA' }}</td>
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
                                    <a href="{{ route('ticket.show', $t->id) }}" class="btn btn-outline">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
