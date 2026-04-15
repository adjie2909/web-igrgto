@extends('layouts.admin')

@section('content')

<div class="card">

    {{-- HEADER --}}
    <div class="header-modern">
        <div>
            <h2>Manajemen Request</h2>
            <p>Approval request barang</p>
        </div>

        <form method="GET">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Cari user..." class="search-box">
        </form>
    </div>

    {{-- TABLE --}}
    <table class="table-modern">
        <thead>
            <tr>
                <th>User</th>
                <th>Divisi</th>
                <th>Status</th>
                <th style="width:90px;">Aksi</th>
            </tr>
        </thead>

        <tbody>
        @forelse($requests as $r)
            <tr>

                {{-- USER --}}
                <td class="user-name">{{ $r->user->name }}</td>

                {{-- DIVISI --}}
                <td>{{ $r->user->division->nama_divisi ?? '-' }}</td>

                {{-- STATUS --}}
                <td>
                    @if($r->status == 0)
                        <span class="badge badge-user">PENDING</span>
                    @elseif($r->status == 1)
                        <span class="badge badge-sm">APPROVED</span>
                    @elseif($r->status == 2)
                        <span class="badge badge-sjm">PROSES</span>
                    @elseif($r->status == 3)
                        <span class="badge badge-sm">SELESAI</span>
                    @else
                        <span class="badge badge-admin">REJECTED</span>
                    @endif
                </td>

            {{-- AKSI --}}
            <td class="aksi">
                <details class="action-menu">
                    <summary class="action-menu__trigger">
                        <span></span><span></span><span></span>
                    </summary>
                    <div class="action-menu__panel">
                        <button class="action-menu__item" type="button" onclick='openDetailModal(@json($r))'>
                            Detail
                        </button>

                        @if($r->status == 0)
                        <form method="POST" action="{{ route('approval.approve', $r->id) }}" class="action-menu__form">
                            @csrf
                            <button type="submit" onclick="return confirm('Approve request ini?')">
                                Approve
                            </button>
                        </form>
                        @endif

                        @if($r->status == 0)
                        <form method="POST" action="{{ route('approval.reject', $r->id) }}" class="action-menu__form action-menu__form--danger">
                            @csrf
                            <input type="hidden" name="reason" value="Ditolak oleh admin">
                            <button type="submit" onclick="return confirm('Reject request ini?')">
                                Reject
                            </button>
                        </form>
                        @endif

                        <a href="{{ route('request.pdf', ['id' => $r->id, 'doc' => strtoupper(str_replace('/', '-', $r->nomor_dokumen ?? 'CHECKLIST-REQUEST'))]) }}" 
                        class="action-menu__item" target="_blank">
                            Checklist
                        </a>

                        <a href="{{ route('request.pdf.serah', ['id' => $r->id, 'doc' => strtoupper(str_replace('/', '-', $r->nomor_serah ?? $r->nomor_dokumen ?? 'SERAH-TERIMA'))]) }}" 
                        class="action-menu__item" target="_blank">
                            Serah
                        </a>
                    </div>
                </details>
            </td>

            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;">Tidak ada data</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{-- PAGINATION --}}
    <div class="pagination-custom">
        @if ($requests->onFirstPage())
            <span>«</span>
        @else
            <a href="{{ $requests->previousPageUrl() }}">«</a>
        @endif

        @for ($i = max(1, $requests->currentPage()-2); $i <= min($requests->lastPage(), $requests->currentPage()+2); $i++)
            @if ($i == $requests->currentPage())
                <span class="active">{{ $i }}</span>
            @else
                <a href="{{ $requests->url($i) }}">{{ $i }}</a>
            @endif
        @endfor

        @if ($requests->hasMorePages())
            <a href="{{ $requests->nextPageUrl() }}">»</a>
        @else
            <span>»</span>
        @endif
    </div>

<div id="detailModal" class="modal">
    <div class="modal-content" style="width:650px;">

        <h3 style="margin-bottom:20px;">Detail Request</h3>

        <div class="detail-grid">
            <div>
                <label>User</label>
                <div id="d_user"></div>
            </div>

            <div>
                <label>Divisi</label>
                <div id="d_division"></div>
            </div>

            <div>
                <label>Nomor</label>
                <div id="d_nomor"></div>
            </div>

            <div>
                <label>Tanggal</label>
                <div id="d_tanggal"></div>
            </div>
        </div>

        <hr>

        <h4 style="margin:15px 0;">Daftar Barang</h4>

        <table class="detail-table">
            <thead>
                <tr>
                    <th>Barang</th>
                    <th>Qty</th>
                </tr>
            </thead>
            <tbody id="d_items"></tbody>
        </table>

        <div style="margin-top:20px; text-align:right;">
            <button class="btn btn-gray" onclick="closeDetailModal()">Tutup</button>
        </div>

    </div>
</div>
<script>
function openDetailModal(data) {

    // isi header
    document.getElementById('d_user').innerText = data.user.name;
    document.getElementById('d_division').innerText = data.user.division.nama_divisi ?? '-';
    document.getElementById('d_nomor').innerText = data.nomor_dokumen ?? '-';
    document.getElementById('d_tanggal').innerText = data.tanggal_request;

    // isi item
    let itemsHtml = '';

    data.details.forEach(item => {
        itemsHtml += `
            <tr>
                <td>${item.barang?.nama_barang ?? item.keterangan}</td>
                <td>${item.qty}</td>
            </tr>
        `;
    });

    document.getElementById('d_items').innerHTML = itemsHtml;

    document.getElementById('detailModal').classList.add('show');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('show');
}

document.getElementById('detailModal').addEventListener('click', function (e) {
    if (e.target === this) {
        closeDetailModal();
    }
});
</script>
@endsection
