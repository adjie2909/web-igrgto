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

            @if(in_array(auth()->user()->role, ['USER','SJM','PGA']))
                <a href="/request"  class="btn btn-primary">
                    Lihat Request
                </a>
            @endif
            @if(auth()->user()->role == 'USER')
                <a href="/request/create" class="btn btn-primary">
                    + Buat Request
                </a>
            @endif
            {{-- 🔥 BULK APPROVAL KHUSUS SAM & SM --}}
            @if(in_array(auth()->user()->role, ['SAM','SM']))
                <a href="{{ route('approval.bulk') }}" class="btn btn-blue">
                    Bulk Approval
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
                <th>Detail</th>
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
                        @elseif($r->status == 4)
                            <span class="status-reject">Ditolak</span>
                        @else
                            <span class="status-selesai">Selesai</span>
                        @endif
                    </td>
                    <td>{{ $r->created_at->format('d-m-Y') }}</td>
                    <td><button class="btn btn-outline btn-detail"
                            data-json='@json($r->load("user.division", "details.barang"))'>
                            Detail
                        </button></td>
                </tr>
            @endforeach
        </table>
    </div>
    {{-- MODAL DETAIL --}}

    <div id="modalDetail" class="modal">
        <div class="modal-content" style="width:600px;">

            <h3 style="margin-bottom:15px;">Detail Request</h3>

            <div class="detail-box">
                <div>
                    <small>User</small>
                    <div id="d_user"></div>
                </div>

                <div>
                    <small>Divisi</small>
                    <div id="d_division"></div>
                </div>

                <div>
                    <small>No Dokumen</small>
                    <div id="d_nomor"></div>
                </div>

                <div>
                    <small>Tanggal</small>
                    <div id="d_tanggal"></div>
                </div>
            </div>

            <hr style="margin:15px 0;">

            <table style="width:100%;">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody id="d_items"></tbody>
            </table>

            <div class="modal-actions">
                <button class="btn btn-outline" id="closeDetail">Tutup</button>
            </div>

        </div>
    </div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const modalDetail = document.getElementById('modalDetail');
    const closeDetail = document.getElementById('closeDetail');

    document.querySelectorAll('.btn-detail').forEach(btn => {
        btn.addEventListener('click', function () {

            const data = JSON.parse(this.dataset.json);

            // HEADER
            document.getElementById('d_user').innerText = data.user?.name ?? '-';
            document.getElementById('d_division').innerText = data.user?.division?.nama_divisi ?? '-';
            document.getElementById('d_nomor').innerText = data.nomor_dokumen ?? '-';
            document.getElementById('d_tanggal').innerText = formatTanggal(data.created_at);

            // DETAIL ITEMS (AMAN)
            let html = '';

            if (data.details && data.details.length > 0) {
                data.details.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.barang?.nama_barang ?? item.keterangan ?? '-'}</td>
                            <td>${item.qty ?? 0}</td>
                        </tr>
                    `;
                });
            } else {
                html = `<tr><td colspan="2">Tidak ada data</td></tr>`;
            }

            document.getElementById('d_items').innerHTML = html;

            modalDetail.classList.add('show');
        });
    });

    // CLOSE BUTTON
    closeDetail.addEventListener('click', function () {
        modalDetail.classList.remove('show');
    });

    // CLICK BACKGROUND CLOSE
    window.addEventListener('click', function (e) {
        if (e.target === modalDetail) {
            modalDetail.classList.remove('show');
        }
    });

});

</script>
@endsection