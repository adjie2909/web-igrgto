@extends('layouts.app')

@section('content')

    <div class="card">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h2>List Request</h2>

            @if(auth()->user()->role == 'SAM' || auth()->user()->role == 'SM')
                <a href="{{ route('approval.bulk') }}" class="btn btn-blue">
                    Bulk Approval
                </a>
            @endif
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
                                    <button class="btn btn-outline btn-detail" data-json='@json($req)'>
                                        Detail
                                    </button>
                                    <form method="POST" action="{{ route('approval.approve', $req->id) }}">
                                        @csrf
                                        <button class="btn btn-blue">
                                            Approve
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-gray btn-reject" data-id="{{ $req->id }}">
                                        Reject
                                    </button>

                                </div>

                            @endif


                            {{-- PGA PROSES --}}
                            @if($user->role == 'PGA' && $req->status == 1)
                                <button class="btn btn-outline btn-detail" data-json='@json($req)'>
                                    Detail
                                </button>
                                <a class="btn btn-outline" href="{{ route('proses', $req->id) }}" target="_blank"
                                    onclick="window.open('{{ route('request.pdf', $req->id) }}', '_blank')">
                                    Proses
                                </a>
                            @endif


                            {{-- PGA SELESAI --}}
                            @if($user->role == 'PGA' && $req->status == 2)
                                <button class="btn btn-outline btn-detail" data-json='@json($req)'>
                                    Detail
                                </button>
                                <a class="btn btn-outline" href="{{ route('selesai', $req->id) }}">
                                    Selesai
                                </a>
                            @endif


                            {{-- DEFAULT --}}
                            @if(
                                    !($isApprover && $req->status == 0) &&
                                    !($user->role == 'PGA' && in_array($req->status, [1, 2]))
                                )
                                <button class="btn btn-outline btn-detail" data-json='@json($req)'>
                                    Detail
                                </button>
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

    {{-- MODAL REJECT --}}

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

    {{-- MODAL SUKSES --}}

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
                        <th>Gambar</th>
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

                    formReject.action = `/approval/${id}/reject`;
                    modalReject.classList.add('show');
                });
            });

            // tutup reject
            if (closeReject) {
                closeReject.addEventListener('click', function () {
                    modalReject.classList.remove('show');
                });
            }


            // MODAL SUCCESS

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


    <!-- MODAL DETAIL -->

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const modalDetail = document.getElementById('modalDetail');
            const closeDetail = document.getElementById('closeDetail');

            document.querySelectorAll('.btn-detail').forEach(btn => {
                btn.addEventListener('click', function () {

                    const data = JSON.parse(this.dataset.json);

                    // HEADER
                    document.getElementById('d_user').innerText = data.user.name;
                    document.getElementById('d_division').innerText = data.user.division?.nama_divisi ?? '-';
                    document.getElementById('d_nomor').innerText = data.nomor_dokumen ?? '-';
                    document.getElementById('d_tanggal').innerText = formatTanggal(data.created_at);

                    // DETAIL ITEMS
                    let html = '';

                    data.details.forEach(item => {
                        html += `
                                    <tr>
                                        <td>${item.barang?.nama_barang ?? item.keterangan}</td>
                                        <td>${item.qty}</td>
                                        <td>
                                            ${item.image
                                ? `<button class="btn btn-outline" onclick="showImage('${item.image}')">
                                                        Lihat
                                                </button>`
                                : '-'
                            }
                                        </td>
                                    </tr>
                                `;
                    });

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

    @if(session('print_serah'))
        <script>
            window.open("{{ route('request.pdf.serah', session('print_serah')) }}", "_blank");
        </script>
    @endif

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
                <img src="/storage/${img}" 
                     style="max-width:80vw; max-height:80vh; border-radius:10px;">
                <button style="
                    position:absolute;
                    top:-10px; right:-10px;
                    background:red; color:white;
                    border:none; border-radius:50%;
                    width:30px; height:30px; cursor:pointer;
                " onclick="this.parentElement.parentElement.remove()">×</button>
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
    <script>
    document.getElementById('checkAll').onclick = function(){
        document.querySelectorAll('input[name="ids[]"]').forEach(cb => {
            cb.checked = this.checked;
        });
    }
    </script>
@endsection