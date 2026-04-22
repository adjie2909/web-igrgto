@extends('layouts.app')

@section('content')
<div class="page-stack">
    <x-public-hero
        eyebrow="Request Monitor"
        title="List Request"
        subtitle="Pantau status approval, proses PGA, dan detail kebutuhan barang dalam satu tampilan yang lebih ringkas."
        :show-meta="false"
    >
        <x-slot:aside>
            <div class="section-actions">
            @if(auth()->user()->role == 'SAM' || auth()->user()->role == 'SM')
                <a href="{{ route('approval.bulk') }}" class="btn btn-primary">Bulk Approval</a>
            @endif
            <a href="{{ route('dashboard') }}" class="btn btn-outline">Kembali</a>
            </div>
        </x-slot:aside>
    </x-public-hero>

    <section class="card table-section">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nodoc</th>
                        <th>User</th>
                        <th>Divisi</th>
                        <th>Status</th>
                        <th>Level</th>
                        <th style="width:90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                        <tr>
                            <td>{{ $req->nomor_dokumen }}</td>
                            <td>{{ $req->user->name }}</td>
                            <td>{{ $req->user->division->nama_divisi ?? '-' }}</td>
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
                            <td><span style="font-size:14px; color:#64748b;">Level {{ $req->current_approval_level }}</span></td>
                            <td class="aksi">
                                @php
                                    $user = auth()->user();
                                    $isApprover =
                                        ($req->current_approval_level == 1 && $user->role == 'SJM') ||
                                        ($req->current_approval_level == 2 && $user->role == 'SAM') ||
                                        ($req->current_approval_level == 3 && $user->role == 'SM');
                                @endphp

                                <details class="action-menu">
                                    <summary class="action-menu__trigger">
                                        <span></span><span></span><span></span>
                                    </summary>
                                    <div class="action-menu__panel">
                                        <button class="action-menu__item btn-detail" data-json='@json($req)' type="button">Detail</button>

                                        @if($isApprover && $req->status == 0)
                                            <form method="POST" action="{{ route('approval.approve', $req->id) }}" class="action-menu__form">
                                                @csrf
                                                <button type="submit">Approve</button>
                                            </form>
                                            <button type="button" class="action-menu__item action-menu__item--danger btn-reject" data-id="{{ $req->id }}">Reject</button>
                                        @elseif($user->role == 'PGA' && $req->status == 1)
                                            <form method="POST" action="{{ route('proses', $req->id) }}" class="action-menu__form js-pga-action-form">
                                                @csrf
                                                <button type="submit">Proses</button>
                                            </form>
                                        @elseif($user->role == 'PGA' && $req->status == 2)
                                            <form method="POST" action="{{ route('selesai', $req->id) }}" class="action-menu__form js-pga-action-form">
                                                @csrf
                                                <button type="submit">Selesai</button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

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

    <div id="modalDetail" class="modal">
        <div class="modal-content" style="width:min(1100px, 95vw);">
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

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>Keterangan</th>
                            <th>Qty</th>
                            <th>Estimasi Harga</th>
                            <th>Gambar</th>
                        </tr>
                    </thead>
                    <tbody id="d_items"></tbody>
                </table>
            </div>

            <div class="modal-actions">
                <button class="btn btn-outline" id="closeDetail" type="button">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalReject = document.getElementById('modalReject');
    const formReject = document.getElementById('formReject');
    const closeReject = document.getElementById('closeReject');

    document.querySelectorAll('.btn-reject').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            formReject.action = `{{ url('/approval') }}/${id}/reject`;
            modalReject.classList.add('show');
        });
    });

    if (closeReject) {
        closeReject.addEventListener('click', function () {
            modalReject.classList.remove('show');
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalDetail = document.getElementById('modalDetail');
    const closeDetail = document.getElementById('closeDetail');

    function formatRupiah(angka) {
        return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
    }

    function formatTanggalSafe(datetime){
        if(!datetime) return '-';
        const date = new Date(datetime);
        if(isNaN(date.getTime())) return datetime;
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    }

    document.querySelectorAll('.btn-detail').forEach(btn => {
        btn.addEventListener('click', function () {
            const data = JSON.parse(this.dataset.json);

            document.getElementById('d_user').innerText = data.user?.name ?? '-';
            document.getElementById('d_division').innerText = data.user?.division?.nama_divisi ?? '-';
            document.getElementById('d_nomor').innerText = data.nomor_dokumen ?? '-';
            document.getElementById('d_tanggal').innerText = formatTanggalSafe(data.created_at);

            let html = '';

            if (data.details && data.details.length > 0) {
                data.details.forEach(item => {
                    const estimasiHarga = item.harga_manual ?? item.barang?.harga_estimasi ?? 0;
                    html += `
                        <tr>
                            <td>${item.barang?.nama_barang ?? '-'}</td>
                            <td>${item.keterangan ?? '-'}</td>
                            <td>${item.qty}</td>
                            <td>${formatRupiah(estimasiHarga)}</td>
                            <td>
                                ${item.image
                                    ? `<button class="btn btn-outline" onclick="showImage('${item.image}')">Lihat</button>`
                                    : '-'
                                }
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `<tr><td colspan="5">Tidak ada data</td></tr>`;
            }

            document.getElementById('d_items').innerHTML = html;
            modalDetail.classList.add('show');
        });
    });

    closeDetail.addEventListener('click', function () {
        modalDetail.classList.remove('show');
    });

    window.addEventListener('click', function (e) {
        if (e.target === modalDetail) {
            modalDetail.classList.remove('show');
        }
    });
});
</script>

<script>
function showImage(img) {
    const modal = document.createElement('div');

    modal.style = `
        position:fixed;
        top:0;left:0;
        width:100%;height:100%;
        background:rgba(0,0,0,0.7);
        display:flex;
        align-items:center;
        justify-content:center;
        z-index:9999;
    `;

    modal.innerHTML = `
        <div style="position:relative;">
            <img src="{{ asset('storage') }}/${img}" style="max-width:80vw; max-height:80vh; border-radius:10px;">
            <button style="position:absolute; top:-10px; right:-10px; background:red; color:white; border:none; border-radius:50%; width:30px; height:30px; cursor:pointer;" onclick="this.parentElement.parentElement.remove()">x</button>
        </div>
    `;

    modal.onclick = function (e) {
        if (e.target === modal) {
            modal.remove();
        }
    };

    document.body.appendChild(modal);
}
</script>

@if(session('print_serah'))
<script>
window.addEventListener('load', function () {
    window.open("{{ route('request.pdf.serah', ['id' => session('print_serah'), 'doc' => session('print_serah_doc')]) }}?token={{ session('print_serah_token') }}", '_blank');
});
</script>
@endif

@if(session('print_checklist'))
<script>
window.addEventListener('load', function () {
    window.open("{{ route('request.pdf', ['id' => session('print_checklist'), 'doc' => session('print_checklist_doc')]) }}", '_blank');
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.js-pga-action-form');

    forms.forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const printWindow = window.open('about:blank', '_blank');
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

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
                    throw new Error(data.message || 'Gagal memproses request');
                }

                if (printWindow) {
                    printWindow.location.href = data.pdf_url;
                }

                window.location.href = "{{ route('request.index') }}";
            } catch (error) {
                if (printWindow) {
                    printWindow.close();
                }

                if (submitBtn) submitBtn.disabled = false;
                alert(error.message || 'Gagal memproses request');
            }
        });
    });
});
</script>

@endsection
