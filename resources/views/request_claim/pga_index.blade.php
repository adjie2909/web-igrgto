@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Pengambilan Barang PGA</h2>
        <form method="GET" action="{{ route('request-claim.index') }}" style="margin:12px 0 16px; display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
            <div>
                <label style="display:block; font-size:13px; color:#64748b; margin-bottom:4px;">Dari Tanggal</label>
                <input type="date" name="date_from" class="input" value="{{ $dateFrom ?? '' }}" required>
            </div>
            <div>
                <label style="display:block; font-size:13px; color:#64748b; margin-bottom:4px;">Sampai Tanggal</label>
                <input type="date" name="date_to" class="input" value="{{ $dateTo ?? '' }}" required>
            </div>
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </form>
        <!-- <p style="font-size:14px; color:#64748b;">
            Permintaan barang dari kuota approved. PGA memproses lalu menyelesaikan serah terima.
        </p> -->

        @if(session('error'))
            <div style="background:#fee2e2; color:#991b1b; padding:10px 12px; border-radius:6px; margin:12px 0;">
                {{ session('error') }}
            </div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>No Permintaan</th>
                    <th>User</th>
                    <th>No Request Awal</th>
                    <th>Tanggal Pengajuan</th>
                    <th>Barang</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($claims as $claim)
                    <tr>
                        <td>{{ $claim->nomor_claim ?? '-' }}</td>
                        <td>{{ $claim->user->name ?? '-' }}<br><small>{{ $claim->user->division->nama_divisi ?? '-' }}</small></td>
                        <td>{{ $claim->requestHeader->nomor_dokumen ?? '-' }}</td>
                        <td>{{ optional($claim->tanggal_claim)->format('d-m-Y') }}</td>
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
                        <td>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                @if($claim->status == 0)
                                    <form method="POST" action="{{ route('request-claim.process', $claim->id) }}">
                                        @csrf
                                        <button class="btn btn-primary">Proses</button>
                                    </form>
                                @endif

                                @if($claim->status == 1)
                                    <form method="POST" action="{{ route('request-claim.complete', $claim->id) }}" class="js-claim-complete-form">
                                        @csrf
                                        <button class="btn btn-blue">Serah Terima</button>
                                    </form>
                                @endif

                                @if(in_array($claim->status, [0, 1]))
                                    <form method="POST" action="{{ route('request-claim.reject', $claim->id) }}" style="display:flex; gap:6px;">
                                        @csrf
                                        <input type="text" name="reason" class="input" placeholder="Alasan" style="width:140px;">
                                        <button class="btn btn-red">Tolak</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            @if(!empty($mustFillDates))
                                Isi Dari Tanggal dan Sampai Tanggal terlebih dahulu, lalu klik Tampilkan.
                            @else
                                Belum ada permintaan barang masuk PGA.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:15px;">
            {{ $claims->links() }}
        </div>
    </div>

    @if(session('print_claim_id'))
    <script>
        window.addEventListener('load', function () {
            window.open(
                "{{ route('request-claim.pdf.serah', ['id' => session('print_claim_id'), 'doc' => session('print_claim_doc')]) }}",
                '_blank'
            );
        });
    </script>
    @endif

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const completeForms = document.querySelectorAll('.js-claim-complete-form');

        completeForms.forEach(form => {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const printWindow = window.open('about:blank', '_blank');
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) submitButton.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const data = await response.json();
                    if (!response.ok || !data.pdf_url) {
                        throw new Error(data.message || 'Gagal proses serah terima');
                    }

                    if (printWindow) {
                        printWindow.location.href = data.pdf_url;
                    }

                    window.location.href = "{{ route('request-claim.index') }}";
                } catch (error) {
                    if (printWindow) {
                        printWindow.close();
                    }

                    if (submitButton) submitButton.disabled = false;
                    alert(error.message || 'Gagal proses serah terima');
                }
            });
        });
    });
    </script>
@endsection
