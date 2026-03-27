@extends('layouts.app')

@section('content')

<div class="card">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h2>List Request</h2>

        <a href="/request/create" class="btn btn-primary">
            + Buat Request
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nodoc</th>
                <th>User</th>
                <th>Divisi</th>
                <th>Barang</th>
                <th>Status</th>
                <th>Level</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
        @foreach($requests as $req)
            <tr>
                <td>{{ $req->nomor_dokumen }}</td>

                <td>{{ $req->user->name }}</td>

                <td>
                    {{ $req->user->division->nama_divisi ?? '-' }}
                </td>

                <td>
                    @foreach($req->details as $detail)
                        <div style="font-size:20px;">
                            {{ $detail->barang->nama_barang ?? $detail->keterangan }} -
                            {{ $detail->qty }}
                        </div>
                    @endforeach
                </td>

                <td>
                    @if($req->status == 0)
                        <span class="status-pending">Pending</span>
                    @elseif($req->status == 1)
                        <span class="status-approved">Approved</span>
                    @elseif($req->status == 2)
                        <span class="status-proses">Diproses</span>
                    @elseif($req->status == 4)
                        <span class="status-reject">Rejected</span>
                    @else
                        <span class="status-selesai">Selesai</span>
                    @endif
                </td>

                <td>
                    <span style="font-size:20px; color:#64748b;">
                        Level {{ $req->current_approval_level }}
                    </span>
                </td>

                <td>
                    @php
                        $user = auth()->user();
                        $isApprover =
                            ($req->current_approval_level == 1 && $user->role == 'SJM') ||
                            ($req->current_approval_level == 2 && $user->role == 'SAM') ||
                            ($req->current_approval_level == 3 && $user->role == 'SM');
                    @endphp

                    {{-- APPROVER (Approve + Reject) --}}
                    @if($isApprover && $req->status == 0)

                    <div style="display:flex; gap:6px;">
                        <a class="btn btn-outline" href="{{ route('approve', $req->id) }}">
                            Approve
                        </a>

                    <button 
                        type="button"
                        class="btn btn-action btn-reject"
                        data-id="{{ $req->id }}">
                        Reject
                    </button>
                    </div>

                    @endif


                    {{-- PGA PROSES --}}
                    @if($user->role == 'PGA' && $req->status == 1)
                        <a class="btn btn-outline" href="{{ route('proses', $req->id) }}" target="_blank"
                        onclick="window.open('{{ route('request.pdf', $req->id) }}', '_blank')">
                            Proses
                        </a>
                    @endif


                    {{-- PGA SELESAI --}}
                    @if($user->role == 'PGA' && $req->status == 2)
                        <a class="btn btn-outline" href="{{ route('selesai', $req->id) }}" target="_blank"
                        onclick="window.open('{{ route('request.pdf.serah', $req->id) }}', '_blank')">
                            Selesai
                        </a>
                    @endif


                    {{-- DEFAULT --}}
                    @if(
                        !($isApprover && $req->status == 0) &&
                        !($user->role == 'PGA' && in_array($req->status, [1,2]))
                    )
                        <span style="color:#94a3b8;">-</span>
                    @endif
                </td>

            </tr>
        @endforeach
        </tbody>
    </table>

</div>
<a href="{{ route('dashboard') }}" class="btn btn-outline">
    Kembali
</a>


<div id="modalReject" class="modal">
    <div class="modal-content">
        <h3>Reject Request</h3>

        <form id="formReject" method="POST">
            @csrf

            <label>Alasan Reject</label>
            <textarea name="reason" class="input" required></textarea>

            <div class="modal-actions">
                <button type="button" class="btn btn-outline" id="closeReject">Batal</button>
                <button type="submit" class="btn btn-danger">Reject</button>
            </div>
        </form>
    </div>
</div>

<div id="modalSuccess" class="modal modal-success">
    <div class="modal-content success-content">

        <div class="success-icon">✔</div>

        <h3>Berhasil</h3>

        <p style="font-size:14px; color:#64748b;">
            Request berhasil dikirim
        </p>

        <button class="btn btn-primary" id="btnCloseSuccess">
            OK
        </button>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // =========================
    // MODAL REJECT
    // =========================
    const modalReject = document.getElementById('modalReject');
    const formReject = document.getElementById('formReject');
    const closeReject = document.getElementById('closeReject');

    document.querySelectorAll('.btn-reject').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;

            console.log('Reject ID:', id); // debug

            formReject.action = `/reject/${id}`;
            modalReject.classList.add('show');
        });
    });

    // tutup reject
    if (closeReject) {
        closeReject.addEventListener('click', function () {
            modalReject.classList.remove('show');
        });
    }

    // =========================
    // MODAL SUCCESS
    // =========================
    const modalSuccess = document.getElementById('modalSuccess');
    const btnCloseSuccess = document.getElementById('btnCloseSuccess');

    @if(session('success'))
        setTimeout(() => {
            modalSuccess.classList.add('show');
        }, 100);

        if (btnCloseSuccess) {
            btnCloseSuccess.addEventListener('click', function () {
                modalSuccess.classList.remove('show');
            });
        }

        setTimeout(() => {
            modalSuccess.classList.remove('show');
        }, 3000);
    @endif

});

document.addEventListener('DOMContentLoaded', function () {

    @if(session('success'))
        const modalSuccess = document.getElementById('modalSuccess');
        const btnCloseSuccess = document.getElementById('btnCloseSuccess');

        setTimeout(() => {
            modalSuccess.classList.add('show');
        }, 100);

        btnCloseSuccess.addEventListener('click', function () {
            modalSuccess.classList.remove('show');
        });

        setTimeout(() => {
            modalSuccess.classList.remove('show');
        }, 3000);
    @endif

});
</script>
@endsection