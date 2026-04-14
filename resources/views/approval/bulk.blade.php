@extends('layouts.app')

@section('content')

    <div class="card">

        <h2 style="margin-bottom:20px;">Approval</h2>

        <form id="bulkApprovalForm" method="POST" action="{{ route('approval.bulk.process') }}">
            @csrf
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>NoDoc</th>
                        <th>User</th>
                        <th>Divisi</th>
                        <th>Barang</th>
                        <th>Keterangan</th>
                        <th>Qty</th>
                        <th>Gambar</th>
                        <th>Status Stok</th>
                        <th>Qty Sisa</th>
                        <th>Qty Kurang</th>
                        <th>Estimasi</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($requests as $req)
                        @foreach($req->details as $d)

                            @php
                                $stok = max(0, (int) ($availableStockMap[$d->barang_id] ?? ($d->barang->stok ?? 0)));
                                $harga = (int) ($d->harga_manual ?? ($d->barang->harga_estimasi ?? 0));
                            @endphp

                            <tr class="request-row">
                                <td>
                                    <input type="checkbox" 
                                        class="check-item"
                                        name="ids[]" 
                                        value="{{ $req->id }}"
                                        data-qty="{{ $d->qty }}"
                                        data-estimasi="0"
                                        data-barang-id="{{ $d->barang_id }}"
                                        data-stok="{{ $stok }}"
                                        data-harga="{{ $harga }}">
                                </td>

                                <td>{{ $req->nomor_dokumen }}</td>
                                <td>{{ $req->user->name }}</td>
                                <td>{{ $req->user->division->nama_divisi ?? '-' }}</td>
                                <td>{{ $d->barang->nama_barang ?? '-' }}</td>
                                <td>{{ $d->keterangan ?? '-' }}</td>
                                <td>{{ $d->qty }}</td>
                                <td>
                                    @if(!empty($d->image))
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <img src="/storage/{{ $d->image }}" alt="gambar item"
                                                style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0;">
                                            <button type="button" class="btn btn-outline"
                                                onclick='showImage(@json($d->image))'>
                                                Lihat
                                            </button>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="stock-status">
                                    <span>-</span>
                                </td>

                                <td class="qty-sisa-cell">0</td>
                                <td class="qty-kurang-cell">0</td>
                                <td class="estimasi-cell">Rp 0</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            <div class="card" style="margin-top:20px; padding:15px; background:#f8fafc;">

                <h3 style="margin-bottom:10px;">Ringkasan (Total yang akan di-approve)</h3>

                <div style="display:flex; gap:40px; font-size:16px;">

                    <div>
                        <b>Total Item:</b><br>
                        <span id="totalItem">0</span>
                    </div>

                    <div>
                        <b>Total Qty:</b><br>
                        <span id="totalQty">0</span>
                    </div>

                    <div>
                        <b>Total Estimasi:</b><br>
                        <span id="totalEstimasi" style="color:green;">
                            Rp 0
                        </span>
                    </div>

                </div>

            </div>
            <button type="button" 
                class="btn btn-blue" 
                style="margin-top:20px;"
                id="btnOpenApproveModal">
                Approve
            </button>
            <p>*List yang tidak di-approve auto Reject</p>

        </form>

    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline">
        Kembali
    </a>

    <div id="modalApprove" class="modal">
        <div class="modal-content" style="max-width:430px;">
            <h3 style="margin-top:0;">Konfirmasi Approval</h3>
            <p style="font-size:14px; color:#64748b;">
                Yakin approve? Yang tidak dicentang akan di-reject.
            </p>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" id="btnCancelApprove">Batal</button>
                <button type="button" class="btn btn-primary" id="btnConfirmApprove">Ya, Approve</button>
            </div>
        </div>
    </div>
<script>

document.addEventListener('DOMContentLoaded', function(){
    const userRole = @json(auth()->user()->role);
    const stockUrlTemplate = @json(route('approval.stock', ['id' => '__ID__']));
    const currentRequestIds = @json($requests->pluck('id')->unique()->values()->all());

    const form = document.getElementById('bulkApprovalForm');
    const modalApprove = document.getElementById('modalApprove');
    const btnOpenApproveModal = document.getElementById('btnOpenApproveModal');
    const btnCancelApprove = document.getElementById('btnCancelApprove');
    const btnConfirmApprove = document.getElementById('btnConfirmApprove');

    const checkAll = document.getElementById('checkAll');
    const checkboxes = Array.from(document.querySelectorAll('.check-item'));

    const totalItemEl = document.getElementById('totalItem');
    const totalQtyEl = document.getElementById('totalQty');
    const totalEstimasiEl = document.getElementById('totalEstimasi');

    // =========================
    // FORMAT RUPIAH
    // =========================
    function formatRupiah(angka){
        return 'Rp ' + angka.toLocaleString('id-ID');
    }

    function buildStockUrl(barangId){
        const url = stockUrlTemplate.replace('__ID__', String(barangId));
        const params = new URLSearchParams();

        if(currentRequestIds.length > 0){
            params.set('exclude_request_ids', currentRequestIds.join(','));
        }

        return params.toString() ? `${url}?${params.toString()}` : url;
    }

    async function refreshRealtimeStock(){
        const uniqueBarangIds = [...new Set(
            checkboxes
                .map(cb => cb.dataset.barangId)
                .filter(barangId => barangId && barangId !== '')
        )];

        if(uniqueBarangIds.length === 0){
            return;
        }

        await Promise.all(uniqueBarangIds.map(async (barangId) => {
            try {
                const response = await fetch(buildStockUrl(barangId), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if(!response.ok){
                    return;
                }

                const data = await response.json();
                checkboxes
                    .filter(cb => cb.dataset.barangId === String(barangId))
                    .forEach(cb => {
                        cb.dataset.stok = String(data.stok_tersedia ?? 0);
                    });
            } catch (error) {
            }
        }));

        hitungTotal();
    }

    function renderStatusStok(){
        const checkedItems = checkboxes.filter(cb => cb.checked);
        const activeItems = checkedItems.length > 0 ? checkedItems : checkboxes;
        const sisaStokMap = {};

        checkboxes.forEach(cb => {
            const row = cb.closest('tr');
            const statusEl = row.querySelector('.stock-status');
            const qtySisaEl = row.querySelector('.qty-sisa-cell');
            const qtyKurangEl = row.querySelector('.qty-kurang-cell');
            const estimasiEl = row.querySelector('.estimasi-cell');

            if(!statusEl || !qtySisaEl || !qtyKurangEl || !estimasiEl) return;

            const isActive = activeItems.includes(cb);
            const qty = parseInt(cb.dataset.qty || 0, 10);
            const barangId = cb.dataset.barangId;
            const stokAwal = parseInt(cb.dataset.stok || 0, 10);
            const harga = parseInt(cb.dataset.harga || 0, 10);

            if(!isActive){
                cb.dataset.estimasi = 0;
                statusEl.innerHTML = '<span style="color:#94a3b8;">-</span>';
                qtySisaEl.innerText = '-';
                qtyKurangEl.innerText = '-';
                estimasiEl.innerText = 'Rp 0';
                return;
            }

            if(!(barangId in sisaStokMap)){
                sisaStokMap[barangId] = stokAwal;
            }

            const sisaSebelum = sisaStokMap[barangId];
            const kurang = Math.max(0, qty - sisaSebelum);
            const estimasi = kurang * harga;
            sisaStokMap[barangId] = Math.max(0, sisaSebelum - qty);
            cb.dataset.estimasi = estimasi;

            if(kurang > 0){
                statusEl.innerHTML = '<span style="color:red;">Kurang</span>';
            } else {
                statusEl.innerHTML = '<span style="color:green;">Ada stok</span>';
            }

            qtySisaEl.innerText = String(sisaSebelum);
            qtyKurangEl.innerText = String(kurang);
            estimasiEl.innerText = formatRupiah(estimasi);
        });
    }

    // =========================
    // HITUNG TOTAL
    // =========================
    function hitungTotal(){
        renderStatusStok();

        let totalItem = 0;
        let totalQty = 0;
        let totalEstimasi = 0;

        checkboxes.forEach(cb => {
            if(cb.checked){
                totalItem++;
                totalQty += parseInt(cb.dataset.qty || 0, 10);
                totalEstimasi += parseInt(cb.dataset.estimasi || 0, 10);
            }
        });

        totalItemEl.innerText = totalItem;
        totalQtyEl.innerText = totalQty;
        totalEstimasiEl.innerText = formatRupiah(totalEstimasi);
    }

    // =========================
    // CHECK ALL
    // =========================
    if(checkAll){
        checkAll.addEventListener('change', function(){
            checkboxes.forEach(cb => {
                cb.checked = checkAll.checked;
            });
            hitungTotal();
        });
    }

    // =========================
    // CHECK PER ITEM
    // =========================
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function(){

            // update checkAll status
            checkAll.checked = checkboxes.every(c => c.checked);

            hitungTotal();
        });
    });

    // initial render
    hitungTotal();
    refreshRealtimeStock();
    setInterval(refreshRealtimeStock, 10000);

    // =========================
    // MODAL APPROVE (CUSTOM)
    // =========================
    if(btnOpenApproveModal){
        btnOpenApproveModal.addEventListener('click', function(){
            modalApprove.classList.add('show');
        });
    }

    if(btnCancelApprove){
        btnCancelApprove.addEventListener('click', function(){
            modalApprove.classList.remove('show');
        });
    }

    if(btnConfirmApprove){
        btnConfirmApprove.addEventListener('click', async function(){
            modalApprove.classList.remove('show');

            if (userRole !== 'SM') {
                form.submit();
                return;
            }

            const printWindow = window.open('about:blank', '_blank');
            btnConfirmApprove.disabled = true;

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
                    throw new Error(data.message || 'Gagal approve bulk');
                }

                if (data.pdf_url && printWindow) {
                    printWindow.location.href = data.pdf_url;
                } else if (printWindow) {
                    printWindow.close();
                }

                window.location.href = "{{ route('approval.bulk') }}";
            } catch (error) {
                if (printWindow) {
                    printWindow.close();
                }

                btnConfirmApprove.disabled = false;
                alert(error.message || 'Gagal approve bulk');
            }
        });
    }

    if(modalApprove){
        modalApprove.addEventListener('click', function(e){
            if(e.target === modalApprove){
                modalApprove.classList.remove('show');
            }
        });
    }

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
            <img src="/storage/${img}" style="max-width:85vw; max-height:85vh; border-radius:10px;">
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
@endsection
