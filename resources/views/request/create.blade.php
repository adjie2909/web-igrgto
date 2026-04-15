@extends('layouts.app')

@section('content')
<div class="page-stack canvas-wide">
    <x-public-hero
        eyebrow="Request Form"
        title="Form Request Barang"
        subtitle="Isi kebutuhan barang dengan lengkap. Jika barang tidak tersedia di master, pilih kategori lain-lain lalu lengkapi keterangannya."
        :show-meta="false"
    />

    <section class="card">
        @if(!empty($hiddenBarangIds))
            <div class="notice" style="margin-bottom:1rem;">
                Beberapa barang disembunyikan karena masih ada sisa kuota approved di divisi Anda. Ambil barangnya lewat
                <a href="{{ route('request-claim.index') }}">menu Ambil Kuota</a>.
            </div>
        @endif

        <form id="form-request" method="POST" action="{{ route('request.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="request-builder">
                <div class="request-builder__header">
                    <div class="form-group" style="max-width:300px; margin:0;">
                        <label>Tanggal Request</label>
                        <input type="date" name="tanggal_request" class="input" value="{{ now()->format('Y-m-d') }}" readonly>
                    </div>

                    <div>
                        <div class="request-builder__title">Daftar Barang</div>
                        <p class="request-builder__hint">Buat setiap barang sebagai satu item. Susunan ini sengaja dibuat lebih ringkas supaya lebih enak dibaca dan diisi.</p>
                    </div>
                </div>

                <div id="request-items" class="request-items">
                    <div class="request-item" data-request-row>
                        <div class="request-item__top">
                            <div class="request-item__index">Item 1</div>

                            <div class="request-item__actions">
                                <details class="action-menu">
                                    <summary class="action-menu__trigger">
                                        <span></span><span></span><span></span>
                                    </summary>
                                    <div class="action-menu__panel">
                                        <button type="button" class="action-menu__item action-menu__item--danger btn-hapus">Hapus</button>
                                    </div>
                                </details>
                            </div>
                        </div>

                        <div class="request-item__grid">
                            <div class="request-item__field">
                                <label>Barang</label>
                                <select name="items[0][barang_id]" class="input barang-select">
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach($barangs as $barang)
                                        <option value="{{ $barang->id }}" data-stok="{{ (int) ($barang->stok_tersedia ?? $barang->stok) }}"
                                            data-stok-asli="{{ (int) $barang->stok }}"
                                            data-harga="{{ $barang->harga_estimasi }}" data-unit="{{ $barang->unit }}"
                                            data-terpakai="{{ (int) ($barang->total_request ?? 0) }}">
                                            {{ $barang->nama_barang }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="request-item__field">
                                <label>Qty</label>
                                <input type="number" name="items[0][qty]" class="input qty-input" placeholder="Jumlah">
                            </div>

                            <div class="request-item__field request-item__field--wide">
                                <label>Keterangan</label>
                                <textarea name="items[0][keterangan]" class="input keterangan-textarea" placeholder="Tambahkan catatan kebutuhan, spesifikasi, atau alasan jika barang tidak tersedia di master"></textarea>
                            </div>

                            <div class="request-item__side">
                                <div class="request-item__field">
                                    <label>Estimasi Harga</label>
                                    <input type="number" name="items[0][harga_manual]" class="input harga-input" placeholder="Isi jika lain-lain" style="display:none;">
                                </div>

                                <div class="info-stok"></div>
                            </div>

                            <div class="request-item__field">
                                <label>Contoh Gambar</label>
                                <input type="file" name="items[0][image]" class="input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="request-builder__footer">
                <button type="button" id="btn-tambah" class="btn btn-outline">Tambah Barang</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline">Kembali</a>
            </div>
        </form>
    </section>

    <div id="modalValidation" class="modal">
        <div class="modal-content">
            <h3 style="margin-top:0;">Peringatan</h3>
            <p style="font-size:14px; color:#64748b; margin-bottom:10px;">Harap isi kolom berikut:</p>
            <div id="validationList" style="font-size:14px; line-height:1.7;"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" id="btnCloseValidation">OK</button>
            </div>
        </div>
    </div>
</div>
<script>
let index = 1;
const APP_BASE_URL = @json(url('/'));

function formatRupiah(angka) {
    return 'Rp ' + angka.toLocaleString('id-ID');
}

function buildStockUrl(barangId){
    return `${APP_BASE_URL}/request/stock/${encodeURIComponent(barangId)}`;
}

function updateOptionStockData(barangId, stokTersedia, totalRequest, unit, stokAsli){
    document.querySelectorAll(`.barang-select option[value="${barangId}"]`).forEach(opt => {
        opt.dataset.stok = String(stokTersedia);
        opt.dataset.terpakai = String(totalRequest ?? 0);
        opt.dataset.stokAsli = String(stokAsli ?? stokTersedia);
        if(unit){
            opt.dataset.unit = unit;
        }
    });
}

async function refreshStockByBarangId(barangId){
    if(!barangId) return;

    try {
        const response = await fetch(buildStockUrl(barangId), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if(!response.ok) {
            console.warn('Gagal membaca stok barang', barangId, response.status);
            return;
        }

        const data = await response.json();
        updateOptionStockData(data.barang_id, data.stok_tersedia, data.total_request, data.unit, data.stok_asli);
    } catch (e) {
    }
}

async function refreshStockSemuaRow(){
    const selects = Array.from(document.querySelectorAll('.barang-select'));
    const ids = [...new Set(selects.map(s => s.value).filter(Boolean))];

    if(ids.length === 0) return;

    await Promise.all(ids.map(id => refreshStockByBarangId(id)));

    const rows = document.querySelectorAll('[data-request-row]');
    rows.forEach(row => hitungEstimasi(row));
}

function showValidationModal(fields) {
    let modal = document.getElementById('modalValidation');
    let list = document.getElementById('validationList');
    let btnClose = document.getElementById('btnCloseValidation');

    if (!modal || !list || !btnClose) return;

    list.innerHTML = fields.map((field) => `- ${field}`).join('<br>');
    modal.classList.add('show');

    btnClose.onclick = function () {
        modal.classList.remove('show');
    };
}

function updateRequestItemLabels() {
    document.querySelectorAll('[data-request-row]').forEach((row, i) => {
        const badge = row.querySelector('.request-item__index');
        if (badge) {
            badge.textContent = `Item ${i + 1}`;
        }
    });
}

document.getElementById('btn-tambah').addEventListener('click', function () {
    let list = document.getElementById('request-items');

    let row = `
        <div class="request-item" data-request-row>
            <div class="request-item__top">
                <div class="request-item__index">Item ${index + 1}</div>

                <div class="request-item__actions">
                    <details class="action-menu">
                        <summary class="action-menu__trigger">
                            <span></span><span></span><span></span>
                        </summary>
                        <div class="action-menu__panel">
                            <button type="button" class="action-menu__item action-menu__item--danger btn-hapus">Hapus</button>
                        </div>
                    </details>
                </div>
            </div>

            <div class="request-item__grid">
                <div class="request-item__field">
                    <label>Barang</label>
                    <select name="items[${index}][barang_id]" class="input barang-select">
                        <option value="">-- Pilih Barang --</option>
                        @foreach($barangs as $barang)
                            <option value="{{ $barang->id }}" data-stok="{{ (int) ($barang->stok_tersedia ?? $barang->stok) }}" data-stok-asli="{{ (int) $barang->stok }}" data-harga="{{ $barang->harga_estimasi }}" data-unit="{{ $barang->unit }}" data-terpakai="{{ (int) ($barang->total_request ?? 0) }}">{{ $barang->nama_barang }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="request-item__field">
                    <label>Qty</label>
                    <input type="number" name="items[${index}][qty]" class="input qty-input" placeholder="Jumlah">
                </div>

                <div class="request-item__field request-item__field--wide">
                    <label>Keterangan</label>
                    <textarea name="items[${index}][keterangan]" class="input" placeholder="Tambahkan catatan kebutuhan, spesifikasi, atau alasan jika barang tidak tersedia di master"></textarea>
                </div>

                <div class="request-item__side">
                    <div class="request-item__field">
                        <label>Estimasi Harga</label>
                        <input type="number" name="items[${index}][harga_manual]" class="input harga-input" placeholder="Isi jika lain-lain" style="display:none;">
                    </div>

                    <div class="info-stok"></div>
                </div>

                <div class="request-item__field">
                    <label>Contoh Gambar</label>
                    <input type="file" name="items[${index}][image]" class="input">
                </div>
            </div>
        </div>
    `;

    list.insertAdjacentHTML('beforeend', row);
    index++;
    updateRequestItemLabels();
});

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('btn-hapus')) {
        e.target.closest('[data-request-row]').remove();
        updateRequestItemLabels();
    }
});

function hitungEstimasi(row){
    let select = row.querySelector('.barang-select');
    let qtyInput = row.querySelector('.qty-input');
    let hargaInput = row.querySelector('.harga-input');

    if(!select || !qtyInput) return;

    let selected = select.options[select.selectedIndex];
    let stok = parseInt(selected.dataset.stok || 0);
    let hargaDefault = parseInt(selected.dataset.harga || 0);
    let unit = selected.dataset.unit || '';
    let hargaManual = parseInt(hargaInput.value || 0);
    let harga = hargaManual > 0 ? hargaManual : hargaDefault;
    let qty = parseInt(qtyInput.value || 0);
    let kurang = Math.max(0, qty - stok);
    let estimasi = kurang * harga;
    let info = row.querySelector('.info-stok');

    let html = `<span>Sisa tersedia: <b>${stok} ${unit}</b></span><br>`;

    if(kurang > 0){
        html += `<span style="color:red;">Kekurangan: ${kurang}</span><br>`;
        html += `<span style="color:green;">Estimasi Biaya: ${formatRupiah(estimasi)}</span>`;
    } else {
        html += `<span style="color:green;">Stok cukup</span>`;
    }

    info.innerHTML = html;
}

document.addEventListener('change', function(e){
    if(e.target.classList.contains('barang-select')){
        let row = e.target.closest('[data-request-row]');
        let hargaInput = row.querySelector('.harga-input');
        let selectedText = e.target.options[e.target.selectedIndex].text.toLowerCase();

        if(selectedText.includes('lain')){
            hargaInput.style.display = 'block';
            hargaInput.placeholder = 'Wajib isi harga';
        } else {
            hargaInput.style.display = 'none';
            hargaInput.value = '';
        }

        const barangId = e.target.value;
        if(barangId){
            refreshStockByBarangId(barangId).then(() => hitungEstimasi(row));
        } else {
            hitungEstimasi(row);
        }
    }
});

document.addEventListener('input', function(e){
    if(e.target.classList.contains('qty-input') || e.target.classList.contains('harga-input')){
        let row = e.target.closest('[data-request-row]');
        hitungEstimasi(row);
    }
});

document.getElementById('form-request').addEventListener('submit', function(e){
    let firstInvalid = null;
    let pesanKosong = [];

    function tandaiKosong(field){
        if(!firstInvalid){
            firstInvalid = field;
        }
        field.style.border = '2px solid red';
    }

    function bersihkanError(field){
        field.style.border = '';
    }

    let tanggal = document.querySelector('input[name="tanggal_request"]');
    bersihkanError(tanggal);
    if(!tanggal.value){
        tandaiKosong(tanggal);
        pesanKosong.push('Tanggal Request');
    }

    let rows = document.querySelectorAll('[data-request-row]');

    for(let i = 0; i < rows.length; i++){
        let row = rows[i];
        let select = row.querySelector('.barang-select');
        let qty = row.querySelector('.qty-input');
        let harga = row.querySelector('.harga-input');
        let image = row.querySelector('input[type="file"]');

        if(!select || !qty || !harga) continue;

        bersihkanError(select);
        bersihkanError(qty);
        bersihkanError(harga);
        if(image){
            bersihkanError(image);
        }

        let selectedOption = select.options[select.selectedIndex];
        let selectedText = selectedOption ? selectedOption.text.toLowerCase() : '';
        let nomorBaris = i + 1;

        if(!select.value){
            tandaiKosong(select);
            pesanKosong.push(`Baris ${nomorBaris}: Barang`);
        }

        if(!qty.value || parseInt(qty.value) <= 0){
            tandaiKosong(qty);
            pesanKosong.push(`Baris ${nomorBaris}: Qty`);
        }

        if(selectedText.includes('lain')){
            if(!harga.value || parseInt(harga.value) <= 0){
                tandaiKosong(harga);
                pesanKosong.push(`Baris ${nomorBaris}: Estimasi Harga`);
            }
        }
    }

    if(pesanKosong.length > 0){
        e.preventDefault();
        showValidationModal(pesanKosong);
        if(firstInvalid){
            firstInvalid.focus();
        }
        return false;
    }
});

document.addEventListener('input', function(e){
    if(e.target.classList.contains('input')){
        e.target.style.border = '';
    }
});

document.addEventListener('change', function(e){
    if(e.target.classList.contains('input')){
        e.target.style.border = '';
    }
});

document.getElementById('modalValidation').addEventListener('click', function(e){
    if(e.target.id === 'modalValidation'){
        e.currentTarget.classList.remove('show');
    }
});

updateRequestItemLabels();
refreshStockSemuaRow();
setInterval(refreshStockSemuaRow, 10000);
</script>
@endsection
