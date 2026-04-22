@extends('layouts.app')

@section('content')
    <div class="page-stack">
        <div class="canvas-wide canvas-wide--approval">
            <div class="card table-section">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Approval</h2>
                        <!-- <p class="card-description">Tinjau request pending per halaman supaya approval lebih cepat tanpa scroll terlalu jauh.</p> -->
                    </div>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline">Kembali</a>
                </div>

                <form id="bulkApprovalForm" method="POST" action="{{ route('approval.bulk.process') }}">
                    @csrf
                    <input type="hidden" name="approve_all_pending" id="approveAllPending" value="0">
                    @foreach($requestRows->getCollection()->pluck('request_id')->unique() as $requestId)
                        <input type="hidden" name="page_request_ids[]" value="{{ $requestId }}">
                    @endforeach

                    <div class="table-wrap table-wrap--wide">
                        <table class="approval-bulk-table">
                            <thead>
                                <tr>
                                    <th style="width:48px;"><input type="checkbox" id="checkAll"></th>
                                    <th>NoDoc</th>
                                    <th>User</th>
                                    <th>Divisi</th>
                                    <th>Barang</th>
                                    <th>Keterangan</th>
                                    <th>Qty Req History</th>
                                    <th>Qty Req Saat Ini</th>
                                    <th>Qty Req Edit</th>
                                    <th>Gambar</th>
                                    <th>Status Stok</th>
                                    <th>Qty Sisa</th>
                                    <th>Qty Kurang</th>
                                    <th>Estimasi</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($requestRows as $row)
                                    @php
                                        $req = $row->requestHeader;
                                        $barang = $row->barang;
                                        $stok = max(0, (int) ($availableStockMap[$row->barang_id] ?? ($barang->stok ?? 0)));
                                        $globalStok = max(0, (int) ($globalAvailableStockMap[$row->barang_id] ?? ($barang->stok ?? 0)));
                                        $harga = (int) ($row->harga_manual ?? ($barang->harga_estimasi ?? 0));
                                    @endphp

                                    <tr class="request-row">
                                        <td>
                                            <input type="checkbox"
                                                class="check-item"
                                                name="ids[]"
                                                value="{{ $row->request_id }}"
                                                data-detail-id="{{ $row->id }}"
                                                data-request-id="{{ $row->request_id }}"
                                                data-qty="{{ $row->qty }}"
                                                data-estimasi="0"
                                                data-barang-id="{{ $row->barang_id }}"
                                                data-stok="{{ $stok }}"
                                                data-base-stok="{{ $globalStok }}"
                                                data-harga="{{ $harga }}">
                                        </td>

                                        <td>{{ $req->nomor_dokumen ?? '-' }}</td>
                                        <td>{{ $req->user->name ?? '-' }}</td>
                                        <td>{{ $req->user->division->nama_divisi ?? '-' }}</td>
                                        <td>{{ $barang->nama_barang ?? '-' }}</td>
                                        <td>{{ $row->keterangan ?? '-' }}</td>
                                        <td class="nowrap">{{ (int) ($row->qty_sebelumnya ?? 0) }}</td>
                                        <td class="nowrap">
                                            {{ (int) ($row->qty_saat_ini ?? $row->qty) }}
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                class="input qty-edit-input"
                                                name="qty_updates[{{ $row->id }}]"
                                                min="1"
                                                step="1"
                                                value="{{ (int) $row->qty }}"
                                                data-detail-id="{{ $row->id }}"
                                                style="width:100px;"
                                            >
                                        </td>
                                        <td>
                                            @if(!empty($row->image))
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <img src="/storage/{{ $row->image }}" alt="gambar item"
                                                        style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0;">
                                                    <button type="button" class="btn btn-outline"
                                                        onclick='showImage(@json($row->image))'>
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
                                @empty
                                    <tr>
                                        <td colspan="14">Tidak ada request pending untuk approval di halaman ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($requestRows->hasPages())
                        <div style="margin-top:16px;">
                            {{ $requestRows->links() }}
                        </div>
                    @endif

                    <div class="card card--soft" style="margin-top:20px; padding:15px;">
                        <h3 style="margin-bottom:10px;">Ringkasan (Total yang akan di-approve)</h3>

                        <div class="approval-summary">
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
                                <span id="totalEstimasi" style="color:green;">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <div class="section-actions" style="margin-top:20px;">
                        <button type="button" class="btn btn-primary" id="btnOpenApproveModal">
                            Approve Halaman Ini
                        </button>
                        <!-- <p id="approvalScopeHint" style="margin:0; color:#6b7280;">*Yang tidak dicentang pada halaman ini akan auto reject.</p> -->
                    </div>
                </form>
            </div>
        </div>

        <div id="modalApprove" class="modal">
            <div class="modal-content" style="max-width:430px;">
                <h3 style="margin-top:0;">Konfirmasi Approval</h3>
                <p id="approvalScopeText" style="font-size:14px; color:#64748b;">
                    Yakin approve halaman ini? Item yang tidak checklist akan di-reject.
                </p>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" id="btnCancelApprove">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmApprove">Ya, Approve</button>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const userRole = @json(auth()->user()->role);
    const stockUrlTemplate = @json(route('approval.stock', ['id' => '__ID__']));
    const currentRequestIds = @json($requestRows->getCollection()->pluck('request_id')->unique()->values()->all());
    const globalApproveAllSummary = @json($globalApprovalSummary);
    const globalBaseStockMap = @json($globalAvailableStockMap);
    const globalPendingRows = @json($globalPendingRows);

    const form = document.getElementById('bulkApprovalForm');
    const modalApprove = document.getElementById('modalApprove');
    const btnOpenApproveModal = document.getElementById('btnOpenApproveModal');
    const btnCancelApprove = document.getElementById('btnCancelApprove');
    const btnConfirmApprove = document.getElementById('btnConfirmApprove');
    const approveAllPendingInput = document.getElementById('approveAllPending');
    const approvalScopeHint = document.getElementById('approvalScopeHint');
    const approvalScopeText = document.getElementById('approvalScopeText');
    const persistedHiddenClass = 'js-persisted-approval-id';

    const checkAll = document.getElementById('checkAll');
    const checkboxes = Array.from(document.querySelectorAll('.check-item'));
    const selectionStorageKey = 'approvalBulkSelectedRows';
    const approveAllStorageKey = 'approvalBulkApproveAllPending';
    let selectedRows = new Map();

    const totalItemEl = document.getElementById('totalItem');
    const totalQtyEl = document.getElementById('totalQty');
    const totalEstimasiEl = document.getElementById('totalEstimasi');

    function buildRowPayload(checkbox){
        return {
            requestId: String(checkbox.dataset.requestId || checkbox.value || ''),
            qty: parseInt(checkbox.dataset.qty || 0, 10),
            barangId: String(checkbox.dataset.barangId || ''),
            harga: parseInt(checkbox.dataset.harga || 0, 10),
            stok: parseInt(checkbox.dataset.stok || 0, 10),
            baseStok: parseInt(checkbox.dataset.baseStok || checkbox.dataset.stok || 0, 10),
        };
    }

    function buildStoredPayloadFromGlobalRow(row){
        return {
            requestId: String(row?.request_id || ''),
            qty: parseInt(row?.qty || 0, 10),
            barangId: String(row?.barang_id || ''),
            harga: parseInt(row?.harga || 0, 10),
            stok: parseInt(row?.base_stok || 0, 10),
            baseStok: parseInt(row?.base_stok || 0, 10),
        };
    }

    try {
        const storedRows = JSON.parse(sessionStorage.getItem(selectionStorageKey) || '{}');
        selectedRows = new Map(
            Object.entries(storedRows && typeof storedRows === 'object' ? storedRows : {}).map(([detailId, payload]) => [
                String(detailId),
                {
                    requestId: String(payload?.requestId || ''),
                    qty: parseInt(payload?.qty || 0, 10),
                    barangId: String(payload?.barangId || ''),
                    harga: parseInt(payload?.harga || 0, 10),
                    stok: parseInt(payload?.stok || 0, 10),
                    baseStok: parseInt(payload?.baseStok || payload?.stok || 0, 10),
                },
            ])
        );
        if (approveAllPendingInput) {
            approveAllPendingInput.value = sessionStorage.getItem(approveAllStorageKey) === '1' ? '1' : '0';
        }
    } catch (error) {
        selectedRows = new Map();
        if (approveAllPendingInput) {
            approveAllPendingInput.value = '0';
        }
    }

    function isApproveAllMode(){
        return approveAllPendingInput && approveAllPendingInput.value === '1';
    }

    function saveSelectionState(){
        try {
            sessionStorage.setItem(selectionStorageKey, JSON.stringify(Object.fromEntries(selectedRows)));
            sessionStorage.setItem(approveAllStorageKey, isApproveAllMode() ? '1' : '0');
        } catch (error) {
        }
    }

    function ensureApproveAllSelectionState(){
        if (!isApproveAllMode()) {
            return;
        }

        selectedRows = new Map(
            (Array.isArray(globalPendingRows) ? globalPendingRows : []).map((row) => [
                String(row.detail_id),
                buildStoredPayloadFromGlobalRow(row),
            ])
        );
    }

    function syncCheckboxesFromState(){
        if (isApproveAllMode()) {
            ensureApproveAllSelectionState();

            checkboxes.forEach(cb => {
                cb.checked = true;
            });
            if (checkAll) {
                checkAll.checked = checkboxes.length > 0;
            }
            return;
        }

        checkboxes.forEach(cb => {
            cb.checked = selectedRows.has(String(cb.dataset.detailId || ''));
        });

        if (checkAll) {
            checkAll.checked = checkboxes.length > 0 && checkboxes.every(cb => cb.checked);
        }
    }

    function setRowSelection(checkbox, checked){
        const detailId = String(checkbox.dataset.detailId || '');
        const requestId = String(checkbox.dataset.requestId || checkbox.value || '');

        if (!detailId || !requestId) {
            return;
        }

        if (checked) {
            selectedRows.set(detailId, buildRowPayload(checkbox));
        } else {
            selectedRows.delete(detailId);
        }
    }

    function syncStoredRowsWithPageData(){
        checkboxes.forEach(cb => {
            const detailId = String(cb.dataset.detailId || '');
            if (!detailId || !selectedRows.has(detailId)) {
                return;
            }

            selectedRows.set(detailId, buildRowPayload(cb));
        });
    }

    function syncQtyInputsFromState(){
        selectedRows.forEach((payload, detailId) => {
            const input = document.querySelector('.qty-edit-input[data-detail-id=\"' + String(detailId) + '\"]');
            const cb = checkboxes.find(cb => String(cb.dataset.detailId || '') === String(detailId));

            if (!input || !cb) {
                return;
            }

            const qty = parseInt(payload?.qty || 0, 10);
            if (!Number.isFinite(qty) || qty <= 0) {
                return;
            }

            input.value = String(qty);
            cb.dataset.qty = String(qty);
        });
    }

    function toggleQtyEditingDisabledState(){
        // Qty edit harus tetap bisa dipakai walau master checkbox aktif.
        document.querySelectorAll('.qty-edit-input').forEach((input) => {
            input.disabled = false;
        });
    }

    function buildSelectionAllocationMap(){
        const remainingStockMap = Object.fromEntries(
            Object.entries(globalBaseStockMap || {}).map(([barangId, stock]) => [String(barangId), parseInt(stock || 0, 10)])
        );
        const allocationMap = new Map();

        selectedRows.forEach((row, detailId) => {
            const barangId = String(row.barangId || '');
            const qty = parseInt(row.qty || 0, 10);
            const harga = parseInt(row.harga || 0, 10);

            if (!(barangId in remainingStockMap)) {
                remainingStockMap[barangId] = parseInt(row.baseStok || 0, 10);
            }

            const sisaSebelum = remainingStockMap[barangId];
            const kurang = Math.max(0, qty - sisaSebelum);
            const sisaSesudah = Math.max(0, sisaSebelum - qty);

            allocationMap.set(String(detailId), {
                qtySisa: sisaSesudah,
                qtyKurang: kurang,
                estimasi: kurang * harga,
                status: kurang > 0 ? 'Kurang' : 'Ada stok',
            });

            remainingStockMap[barangId] = sisaSesudah;
        });

        return allocationMap;
    }

    function syncApprovalScopeCopy(){
        const approveAll = isApproveAllMode();

        if (approvalScopeHint) {
            approvalScopeHint.innerText = approveAll
                ? '*Checkbox master aktif: semua data pending akan ikut ter-approve.'
                : '*Yang tidak dicentang pada halaman ini akan auto reject.';
        }

        if (approvalScopeText) {
            approvalScopeText.innerText = approveAll
                ? 'Yakin approve semua data pending? Saat checkbox master aktif, approval tidak dibatasi halaman.'
                : 'Yakin approve halaman ini? Yang tidak dicentang di halaman aktif akan di-reject.';
        }
    }

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
        const allocationMap = buildSelectionAllocationMap();

        checkboxes.forEach(cb => {
            const row = cb.closest('tr');
            const statusEl = row.querySelector('.stock-status');
            const qtySisaEl = row.querySelector('.qty-sisa-cell');
            const qtyKurangEl = row.querySelector('.qty-kurang-cell');
            const estimasiEl = row.querySelector('.estimasi-cell');

            if(!statusEl || !qtySisaEl || !qtyKurangEl || !estimasiEl) return;
            const detailId = String(cb.dataset.detailId || '');
            const allocation = allocationMap.get(detailId);

            if(!allocation){
                cb.dataset.estimasi = 0;
                statusEl.innerHTML = '<span style="color:#94a3b8;">-</span>';
                qtySisaEl.innerText = '-';
                qtyKurangEl.innerText = '-';
                estimasiEl.innerText = 'Rp 0';
                return;
            }

            cb.dataset.estimasi = allocation.estimasi;

            if(allocation.qtyKurang > 0){
                statusEl.innerHTML = '<span style="color:red;">Kurang</span>';
            } else {
                statusEl.innerHTML = '<span style="color:green;">Ada stok</span>';
            }

            qtySisaEl.innerText = String(allocation.qtySisa);
            qtyKurangEl.innerText = String(allocation.qtyKurang);
            estimasiEl.innerText = formatRupiah(allocation.estimasi);
        });
    }

    function hitungTotal(){
        renderStatusStok();

        syncStoredRowsWithPageData();

        let totalItem = 0;
        let totalQty = 0;
        let totalEstimasi = 0;

        if (isApproveAllMode()) {
            totalItem = parseInt(globalApproveAllSummary?.total_item || 0, 10);
            totalQty = parseInt(globalApproveAllSummary?.total_qty || 0, 10);
            totalEstimasi = parseInt(globalApproveAllSummary?.total_estimasi || 0, 10);
        } else {
            const allocationMap = buildSelectionAllocationMap();

            selectedRows.forEach((row, detailId) => {
                totalItem++;
                totalQty += parseInt(row.qty || 0, 10);
                totalEstimasi += parseInt(allocationMap.get(String(detailId))?.estimasi || 0, 10);
            });
        }

        totalItemEl.innerText = totalItem;
        totalQtyEl.innerText = totalQty;
        totalEstimasiEl.innerText = formatRupiah(totalEstimasi);
        saveSelectionState();
    }

    if(checkAll){
        checkAll.addEventListener('change', function(){
            if (approveAllPendingInput) {
                approveAllPendingInput.value = checkAll.checked ? '1' : '0';
            }

            if (checkAll.checked) {
                ensureApproveAllSelectionState();
            } else {
                selectedRows.clear();
            }

            syncCheckboxesFromState();
            saveSelectionState();
            syncApprovalScopeCopy();
            toggleQtyEditingDisabledState();
            hitungTotal();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function(){
            if (approveAllPendingInput && isApproveAllMode()) {
                approveAllPendingInput.value = '0';
                ensureApproveAllSelectionState();
            }

            setRowSelection(cb, cb.checked);
            syncCheckboxesFromState();
            saveSelectionState();
            syncApprovalScopeCopy();
            hitungTotal();
        });
    });

    if (isApproveAllMode()) {
        ensureApproveAllSelectionState();
        saveSelectionState();
    }

    syncQtyInputsFromState();
    toggleQtyEditingDisabledState();

    syncCheckboxesFromState();
    syncApprovalScopeCopy();
    hitungTotal();
    refreshRealtimeStock();
    setInterval(refreshRealtimeStock, 10000);

    document.querySelectorAll('.qty-edit-input').forEach((input) => {
        input.addEventListener('input', () => {
            const detailId = String(input.dataset.detailId || '');
            if (!detailId) return;

            const cb = checkboxes.find(cb => String(cb.dataset.detailId || '') === detailId);
            if (!cb) return;

            const parsed = parseInt(input.value || '0', 10);
            const qty = Math.max(1, Number.isFinite(parsed) ? parsed : 1);

            cb.dataset.qty = String(qty);

            if (selectedRows.has(detailId)) {
                selectedRows.set(detailId, buildRowPayload(cb));
            }

            hitungTotal();
        });
    });

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
        btnConfirmApprove.addEventListener('click', function(){
            modalApprove.classList.remove('show');
            btnConfirmApprove.disabled = true;

            form.querySelectorAll(`.${persistedHiddenClass}`).forEach(input => input.remove());

            if (!isApproveAllMode()) {
                const uniqueRequestIds = [...new Set([...selectedRows.values()].map(row => String(row.requestId || '')).filter(Boolean))];

                uniqueRequestIds.forEach(requestId => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = String(requestId);
                    input.className = persistedHiddenClass;
                    form.appendChild(input);
                });
            }

            try {
                sessionStorage.removeItem(selectionStorageKey);
                sessionStorage.removeItem(approveAllStorageKey);
            } catch (error) {
            }

            form.submit();
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
            " onclick="this.parentElement.parentElement.remove()">x</button>
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

@endsection
