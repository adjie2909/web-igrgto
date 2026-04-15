@extends('layouts.app')

@section('content')
    <div class="page-stack">
    <x-public-hero
        eyebrow="Warehouse Flow"
        title="Pengambilan Barang PGA"
        subtitle="Permintaan barang dari kuota approved. PGA memproses lalu menyelesaikan serah terima."
    />

    <div class="card">
        <form method="GET" action="{{ route('request-claim.index') }}" style="margin:0 0 16px; display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
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
                    <th style="width:90px;">Aksi</th>
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
                        <td class="aksi">
                            <details class="action-menu">
                                <summary class="action-menu__trigger">
                                    <span></span><span></span><span></span>
                                </summary>
                                <div class="action-menu__panel">
                                    @if($claim->status == 0)
                                        <form method="POST" action="{{ route('request-claim.process', $claim->id) }}" class="action-menu__form">
                                            @csrf
                                            <button type="submit">Proses</button>
                                        </form>
                                    @endif

                                    @if($claim->status == 1)
                                        <form method="POST" action="{{ route('request-claim.complete', $claim->id) }}" class="action-menu__form js-claim-complete-form">
                                            @csrf
                                            <button type="submit">Serah Terima</button>
                                        </form>
                                    @endif

                                    @if(in_array($claim->status, [0, 1]))
                                        <form method="POST" action="{{ route('request-claim.reject', $claim->id) }}" class="action-menu__form action-menu__form--danger js-claim-reject-form">
                                            @csrf
                                            <input type="hidden" name="reason" value="">
                                            <button type="button" class="js-claim-reject-button">Tolak</button>
                                        </form>
                                    @endif
                                </div>
                            </details>
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
        const rejectForms = document.querySelectorAll('.js-claim-reject-form');

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

        rejectForms.forEach(form => {
            const button = form.querySelector('.js-claim-reject-button');
            const reasonInput = form.querySelector('input[name="reason"]');

            if (!button || !reasonInput) {
                return;
            }

            button.addEventListener('click', function () {
                const reason = window.prompt('Masukkan alasan penolakan:');

                if (reason === null) {
                    return;
                }

                const trimmedReason = reason.trim();
                if (!trimmedReason) {
                    alert('Alasan penolakan wajib diisi');
                    return;
                }

                reasonInput.value = trimmedReason;
                form.submit();
            });
        });
    });
    </script>
@endsection
