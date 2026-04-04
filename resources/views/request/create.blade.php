@extends('layouts.app')

@section('content')


    <div class="card" style="max-width:1500px; margin:auto;">

        <h2 style="margin-bottom:20px;">Form Request Barang</h2>
        <p style="font-size:13px; color:#64748b;">
            Silakan isi kebutuhan barang dengan lengkap <br>
            Jika barang tidak tersedia didalam master barang, harap isi di keterangan
        </p>

        <form id="form-request" method="POST" action="{{ route('request.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- TANGGAL -->
            <div style="margin-bottom:20px; max-width:300px; ">
                <label style="display:block; margin-bottom:5px;">Tanggal Request</label>
                <input type="date" name="tanggal_request" class="input">
            </div>

            <!-- TABLE -->
            <table id="table-barang">
                <tr>
                    <th style="width:220px;">Barang</th>
                    <th style="width:120px;">Qty</th>
                    <th>Keterangan</th>
                    <th style="width:120px;">Estimasi Harga</th>
                    <th style="width:200px;">Contoh Gambar</th>
                    <th style="width:100px;">Aksi</th>
                </tr>

                <tr>
                    <td>
                        <select name="items[${index}][barang_id]" class="input barang-select">
                            <option value="">-- Pilih Barang --</option>
                            @foreach($barangs as $barang)
                                <option value="{{ $barang->id }}" data-stok="{{ $barang->stok }}"
                                    data-harga="{{ $barang->harga_estimasi }}" data-unit="{{ $barang->unit }}"
                                    data-terpakai="{{ $barang->total_request ?? 0 }}">
                                    {{ $barang->nama_barang }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td>
                        <input type="number" name="items[${index}][qty]" class="input qty-input">
                        <div class="info-stok" style="font-size:12px; margin-top:5px;"></div>
                    </td>

                    <td>
                        <textarea name="items[${index}][keterangan]" class="input keterangan-textarea"
                            placeholder="Isi jika barang tidak tersedia"></textarea>
                    </td>
                    <td>
                        <input type="number" name="items[${index}][harga_manual]" class="input harga-input"
                            placeholder="Harga" style="display:none;">
                    </td>
                    <td>
                        <input type="file" name="items[${index}][image]" class="input">
                    </td>

                    <td>
                        <button type="button" class="btn btn-outline btn-hapus">Hapus</button>
                    </td>
                </tr>
            </table>

            <!-- BUTTON -->
            <div style="margin-top:15px; display:flex; gap:10px;">
                <button type="button" id="btn-tambah" class="btn btn-outline">+ Tambah Barang</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{  route('dashboard') }}" class="btn btn-outline">
                    Kembali
                </a>
            </div>

        </form>
    </div>
<script>
let index = 1;

// ===============================
// FORMAT RUPIAH
// ===============================
function formatRupiah(angka) {
    return 'Rp ' + angka.toLocaleString('id-ID');
}

// ===============================
// TAMBAH ROW
// ===============================
document.getElementById('btn-tambah').addEventListener('click', function () {

    let table = document.getElementById('table-barang');

    let row = `
        <tr>
            <td>
                <select name="items[${index}][barang_id]" class="input barang-select">
                    <option value="">-- Pilih Barang --</option>
                    @foreach($barangs as $barang)
                        <option 
                            value="{{ $barang->id }}"
                            data-stok="{{ $barang->stok }}"
                            data-harga="{{ $barang->harga_estimasi }}"
                            data-unit="{{ $barang->unit }}"
                        >
                            {{ $barang->nama_barang }}
                        </option>
                    @endforeach
                </select>
            </td>

            <td>
                <input type="number" name="items[${index}][qty]" class="input qty-input">
                <div class="info-stok" style="font-size:12px; margin-top:5px;"></div>
            </td>

            <td>
                <textarea 
                    name="items[${index}][keterangan]" 
                    class="input"
                    placeholder="Isi jika barang tidak tersedia"
                ></textarea>
            </td>

            <td>
                <input type="number" 
                    name="items[${index}][harga_manual]" 
                    class="input harga-input"
                    placeholder="Harga"
                    style="display:none;">
            </td>

            <td>
                <input type="file" name="items[${index}][image]" class="input">
            </td>

            <td>
                <button type="button" class="btn btn-outline btn-hapus">Hapus</button>
            </td>
        </tr>
    `;

    table.insertAdjacentHTML('beforeend', row);
    index++;
});


// ===============================
// HAPUS ROW
// ===============================
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('btn-hapus')) {
        e.target.closest('tr').remove();
    }
});


// ===============================
// HITUNG ESTIMASI (CORE LOGIC)
// ===============================
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

    let html = `<span>Stok: <b>${stok} ${unit}</b></span><br>`;

    if(kurang > 0){
        html += `<span style="color:red;">Kekurangan: ${kurang}</span><br>`;
        html += `<span style="color:green;">Estimasi Biaya: ${formatRupiah(estimasi)}</span>`;
    } else {
        html += `<span style="color:green;">✔️ Stok cukup</span>`;
    }

    info.innerHTML = html;
}


// ===============================
// EVENT: PILIH BARANG
// ===============================
document.addEventListener('change', function(e){

    if(e.target.classList.contains('barang-select')){

        let row = e.target.closest('tr');
        let hargaInput = row.querySelector('.harga-input');

        let selectedText = e.target.options[e.target.selectedIndex].text.toLowerCase();

        // 🔥 tampilkan harga manual jika lain-lain
        if(selectedText.includes('lain')){
            hargaInput.style.display = 'block';
            hargaInput.placeholder = 'Wajib isi harga';
        } else {
            hargaInput.style.display = 'none';
            hargaInput.value = '';
        }

        hitungEstimasi(row);
    }

});


// ===============================
// EVENT: QTY INPUT
// ===============================
document.addEventListener('input', function(e){

    if(e.target.classList.contains('qty-input')){
        let row = e.target.closest('tr');
        hitungEstimasi(row);
    }

});


// ===============================
// EVENT: HARGA MANUAL INPUT
// ===============================
document.addEventListener('input', function(e){

    if(e.target.classList.contains('harga-input')){
        let row = e.target.closest('tr');
        hitungEstimasi(row);
    }

});


// ===============================
// VALIDASI SUBMIT
// ===============================
document.getElementById('form-request').addEventListener('submit', function(e){

    let rows = document.querySelectorAll('#table-barang tr');

    for(let i = 1; i < rows.length; i++){

        let row = rows[i];

        let select = row.querySelector('.barang-select');
        let harga = row.querySelector('.harga-input');

        if(!select) continue;

        let selectedOption = select.options[select.selectedIndex];
        let selectedText = selectedOption ? selectedOption.text.toLowerCase() : '';

        if(selectedText.includes('lain')){
            if(!harga.value || parseInt(harga.value) <= 0){

                alert('Barang "Lain-lain" wajib mengisi estimasi harga!');

                harga.focus();
                harga.style.border = '2px solid red';

                e.preventDefault();
                return false; // 🔥 PENTING
            }
        }
    }

});
</script>
@endsection