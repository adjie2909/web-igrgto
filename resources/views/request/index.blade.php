@extends('layouts.app')

@section('content')

    <style>
        .btn-aksi-size {
            width: 88px;
            height: 34px;
            padding: 0 10px;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            box-sizing: border-box;
        }

        .aksi-group {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .aksi-group form {
            margin: 0;
        }
    </style>

    <div class="card">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h2>List Request</h2>

            @if(auth()->user()->role == 'SAM' || auth()->user()->role == 'SM')
                <a href="{{ route('approval.bulk') }}" class="btn btn-blue">
                    Bulk Approval
                </a>
            @endif
            <!-- <a href="{{ route('request.create') }}" class="btn btn-primary">
                + Buat Request
            </a> -->
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

                                <div class="aksi-group">
                                    <button class="btn btn-outline btn-detail btn-aksi-size" data-json='@json($req)'>
                                        Detail
                                    </button>
                                    <form method="POST" action="{{ route('approval.approve', $req->id) }}" class="{{ $user->role == 'SM' ? 'js-sm-approve-form' : '' }}">
                                        @csrf
                                        <button class="btn btn-blue btn-aksi-size">
                                            Approve
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-gray btn-reject btn-aksi-size" data-id="{{ $req->id }}">
                                        Reject
                                    </button>

                                </div>

                            @endif


                            {{-- PGA PROSES --}}
                            @if($user->role == 'PGA' && $req->status == 1)
                                <div class="aksi-group">
                                    <button class="btn btn-outline btn-detail btn-aksi-size" data-json='@json($req)'>
                                        Detail
                                    </button>
                                    <form method="POST" action="{{ route('proses', $req->id) }}" class="js-pga-action-form">
                                        @csrf
                                        <button type="submit" class="btn btn-outline btn-aksi-size">
                                            Proses
                                        </button>
                                    </form>
                                </div>
                            @endif

                            
                            {{-- PGA SELESAI --}}
                            @if($user->role == 'PGA' && $req->status == 2)
                                <div class="aksi-group">
                                    <button class="btn btn-outline btn-detail btn-aksi-size" data-json='@json($req)'>
                                        Detail
                                    </button>
                                    <form method="POST" action="{{ route('selesai', $req->id) }}" class="js-pga-action-form">
                                        @csrf
                                        <button type="submit" class="btn btn-outline btn-aksi-size">
                                            Selesai
                                        </button>
                                    </form>
                                </div>
                            @endif



                            {{-- DEFAULT --}}
                            @if(
                                    !($isApprover && $req->status == 0) &&
                                    !($user->role == 'PGA' && in_array($req->status, [1, 2]))
                                )
                                <button class="btn btn-outline btn-detail btn-aksi-size" data-json='@json($req)'>
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

            <p style="font-size:14px; color:#64748b;" id="successMessage">
                {{ session('success') }}
            </p>

            <button class="btn btn-primary" id="btnCloseSuccess">
                OK
            </button>

        </div>
    </div>


    {{-- MODAL DETAIL --}}

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

            <table style="width:100%;">
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

                    formReject.action = `{{ url('/approval') }}/${id}/reject`;
                    modalReject.classList.add('show');
                });
            });

            // tutup reject
            if (closeReject) {
                closeReject.addEventListener('click', function () {
                    modalReject.classList.remove('show');
                });
            }
        });
    </script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    @if(session('success'))

        const modal = document.getElementById('modalSuccess');
        const btn = document.getElementById('btnCloseSuccess');

        if(modal){
            modal.classList.add('show');

            if(btn){
                btn.onclick = function(){
                    modal.classList.remove('show');
                }
            }

            setTimeout(() => {
                modal.classList.remove('show');
            }, 3000);
        }

    @endif

});
</script>

    <!-- MODAL DETAIL -->

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

                    // HEADER
                    document.getElementById('d_user').innerText = data.user?.name ?? '-';
                    document.getElementById('d_division').innerText = data.user?.division?.nama_divisi ?? '-';
                    document.getElementById('d_nomor').innerText = data.nomor_dokumen ?? '-';
                    document.getElementById('d_tanggal').innerText = formatTanggalSafe(data.created_at);

                    // DETAIL ITEMS
                    let html = '';

                    if (data.details && data.details.length > 0) {
                        data.details.forEach(item => {
                            const estimasiHarga = item.harga_manual ?? item.barang?.harga_estimasi ?? 0;
                            html += `
                                    <tr>
                                        <td>${item.barang?.nama_barang ?? "-"}</td>
                                        <td>${item.keterangan ?? "-"}</td>
                                        <td>${item.qty}</td>
                                        <td>${formatRupiah(estimasiHarga)}</td>
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
                    } else {
                        html = `<tr><td colspan="5">Tidak ada data</td></tr>`;
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
                <img src="{{ asset('storage') }}/${img}" 
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
    const checkAllEl = document.getElementById('checkAll');
    if (checkAllEl) {
        checkAllEl.onclick = function(){
            document.querySelectorAll('input[name="ids[]"]').forEach(cb => {
                cb.checked = this.checked;
            });
        }
    }
    </script>

    @if(session('print_serah'))
    <script>
        window.addEventListener('load', function () {
            window.open(
                "{{ route('request.pdf.serah', ['id' => session('print_serah'), 'doc' => session('print_serah_doc')]) }}?token={{ session('print_serah_token') }}",
                '_blank'
            );
        });
    </script>
    @endif

    @if(session('print_checklist'))
    <script>
        window.addEventListener('load', function () {
            window.open(
                "{{ route('request.pdf', ['id' => session('print_checklist'), 'doc' => session('print_checklist_doc')]) }}",
                '_blank'
            );
        });
    </script>
    @endif

    @if(session('print_approval_ids'))
    <script>
        window.addEventListener('load', function () {
            window.open(
                "{{ route('approval.pdf.sm', ['ids' => session('print_approval_ids'), 'doc' => session('print_approval_doc')]) }}",
                '_blank'
            );
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

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const smForms = document.querySelectorAll('.js-sm-approve-form');

        smForms.forEach(form => {
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
                    if (!response.ok) {
                        throw new Error(data.message || 'Gagal approve request');
                    }

                    if (data.pdf_url && printWindow) {
                        printWindow.location.href = data.pdf_url;
                    } else if (printWindow) {
                        printWindow.close();
                    }

                    window.location.href = "{{ route('request.index') }}";
                } catch (error) {
                    if (printWindow) {
                        printWindow.close();
                    }

                    if (submitBtn) submitBtn.disabled = false;
                    alert(error.message || 'Gagal approve request');
                }
            });
        });
    });
    </script>

@endsection
