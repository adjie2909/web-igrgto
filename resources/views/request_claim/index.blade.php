@extends('layouts.app')

@section('content')
    <div class="page-stack">
    <x-public-hero
        {{--eyebrow="Warehouse Flow"--}}
        title="Pengambilan Barang GA"
        {{--subtitle="Gunakan sisa kuota approved milik divisi. Permintaan ini langsung masuk ke PGA."--}}
    />

    <div class="card">

        @if(session('error'))
            <div style="background:#fee2e2; color:#991b1b; padding:10px 12px; border-radius:6px; margin:12px 0;">
                {{ session('error') }}
            </div>
        @endif

        @if($quotaRows->isEmpty())
            <div style="padding:16px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; margin-top:15px;">
                Tidak ada sisa pengambilan barang. Jika butuh barang baru, silakan buat request awal.
            </div>
            <div style="margin-top:15px;">
                <a href="{{ route('request.create') }}" class="btn btn-primary">Request Awal Baru</a>
            </div>
        @else
            <form method="POST" action="{{ route('request-claim.store') }}" style="margin-top:15px;">
                @csrf

                <table>
                    <thead>
                        <tr>
                            <th>No Request</th>
                            <th>Pemohon</th>
                            <th>Barang</th>
                            <th>Approved</th>
                            <th>Sudah Diminta</th>
                            <th>Sisa Kuota</th>
                            <th>Qty Minta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quotaRows as $i => $row)
                            <tr>
                                <input type="hidden" name="items[{{ $i }}][request_detail_id]" value="{{ $row->detail->id }}">
                                <td>{{ $row->request->nomor_dokumen ?? '-' }}</td>
                                <td>{{ $row->request->user->name ?? '-' }}</td>
                                <td>{{ $row->barang->nama_barang ?? ($row->detail->keterangan ?? '-') }}</td>
                                <td>{{ $row->approved_qty }} {{ $row->barang->unit ?? '' }}</td>
                                <td>{{ $row->claimed_qty }} {{ $row->barang->unit ?? '' }}</td>
                                <td><b>{{ $row->remaining_qty }} {{ $row->barang->unit ?? '' }}</b></td>
                                <td>
                                    <input
                                        type="number"
                                        name="items[{{ $i }}][qty]"
                                        class="input"
                                        min="1"
                                        max="{{ $row->remaining_qty }}"
                                        data-custom-validity="1"
                                        data-field-label="Qty Minta"
                                        data-item-name="{{ $row->barang->nama_barang ?? ($row->detail->keterangan ?? 'Barang') }}"
                                        data-unit="{{ $row->barang->unit ?? '' }}"
                                        placeholder="Max {{ $row->remaining_qty }}"
                                        style="width:120px;"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="margin-top:15px; display:flex; gap:10px;">
                    <button class="btn btn-primary">Kirim ke PGA</button>
                </div>
            </form>
        @endif
    </div>

    <div class="card">
        <h3>Riwayat Permintaan Dari Kuota</h3>

        <table>
            <thead>
                <tr>
                    <th>No Permintaan</th>
                    <th>No Request Awal</th>
                    <th>Peminta</th>
                    <th>Barang</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($claims as $claim)
                    <tr>
                        <td>{{ $claim->nomor_claim ?? '-' }}</td>
                        <td>{{ $claim->requestHeader->nomor_dokumen ?? '-' }}</td>
                        <td>{{ $claim->user->name ?? '-' }}</td>
                        <td>
                            @foreach($claim->details as $detail)
                                <div>{{ $detail->barang->nama_barang ?? '-' }}: {{ $detail->qty }} {{ $detail->barang->unit ?? '' }}</div>
                            @endforeach
                        </td>
                        <td>
                            @if($claim->status == 0)
                                <span class="status-pending">Menunggu PGA</span>
                            @elseif($claim->status == 1)
                                <span class="status-proses">Diproses PGA</span>
                            @elseif($claim->status == 2)
                                <span class="status-selesai">Selesai</span>
                            @else
                                <span class="status-reject">Ditolak</span>
                            @endif
                        </td>
                        <td>{{ optional($claim->tanggal_claim)->format('d-m-Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada permintaan barang dari kuota.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:15px;">
            {{ $claims->links() }}
        </div>
    </div>
    </div>
@endsection
