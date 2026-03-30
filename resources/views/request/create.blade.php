@extends('layouts.app')

@section('content')


    <div class="card" style="max-width:900px; margin:auto;">

        <h2 style="margin-bottom:20px;">Form Request Barang</h2>
        <p style="font-size:13px; color:#64748b;">
            Silakan isi kebutuhan barang dengan lengkap <br>
            Jika barang tidak tersedia didalam master barang, harap isi di keterangan
        </p>

        <form method="POST" action="{{ route('request.store') }}" enctype="multipart/form-data">
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
                    <th style="width:200px;">Contoh Gambar</th>
                    <th style="width:100px;">Aksi</th>
                </tr>

                <tr>
                    <td>
                        <select name="items[0][barang_id]" class="input barang-select">
                            <option value="">-- Pilih Barang --</option>
                            @foreach($barangs as $barang)
                                <option 
                                    value="{{ $barang->id }}"
                                    data-stok="{{ $barang->stok }}"
                                    data-harga="{{ $barang->harga_estimasi }}"
                                    data-unit="{{ $barang->unit }}"
                                    data-terpakai="{{ $barang->total_request ?? 0 }}"
                                >
                                    {{ $barang->nama_barang }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td>
                        <input type="number" name="items[0][qty]" class="input qty-input">
                        <div class="info-stok" style="font-size:12px; margin-top:5px;"></div>
                    </td>

                    <td>
                        <input type="text" name="items[0][keterangan]" class="input">
                    </td>
                    <td>
                        <input type="file" name="items[0][image]" class="input">
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
                    <input type="text" name="items[${index}][keterangan]" class="input">
                </td>

                <td>
                    <button type="button" class="btn btn-outline btn-hapus">Hapus</button>
                </td>
            </tr>
            `;

            table.insertAdjacentHTML('beforeend', row);

            index++;
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-hapus')) {
                e.target.closest('tr').remove();
            }
        });
    </script>
<script>

function formatRupiah(angka) {
    return 'Rp ' + angka.toLocaleString('id-ID');
}

// 🔥 EVENT SAAT PILIH BARANG
document.addEventListener('change', function(e){

    if(e.target.classList.contains('barang-select')){

        let row = e.target.closest('tr');
        let selected = e.target.options[e.target.selectedIndex];

        let stok = parseInt(selected.dataset.stok || 0);
        let unit = selected.dataset.unit || '';

        let info = row.querySelector('.info-stok');

        info.innerHTML = `Stok tersedia: <b>${stok} ${unit}</b>`;
    }

});

// 🔥 EVENT SAAT INPUT QTY (INI YANG BELUM JALAN)
document.addEventListener('input', function(e){

    if(e.target.classList.contains('qty-input')){

        let row = e.target.closest('tr');
        let select = row.querySelector('.barang-select');
        let selected = select.options[select.selectedIndex];

        if(!select.value){
            return;
        }

        let stok = parseInt(selected.dataset.stok || 0);
        let harga = parseInt(selected.dataset.harga || 0);
        let unit = selected.dataset.unit || '';

        let qty = parseInt(e.target.value || 0);


        let kurang = Math.max(0, qty- stok);
        let estimasi = kurang * harga;

        let info = row.querySelector('.info-stok');

        let html = `
        <span>Stok: <b>${stok} ${unit}</b></span><br>
        `;

        if(kurang > 0){
            html += `<span style="color:red;">Kekurangan: ${kurang}</span><br>`;
            html += `<span style="color:green;">Estimasi Biaya: ${formatRupiah(estimasi)}</span>`;
        } else {
            html += `<span style="color:green;">✔️ Stok cukup</span>`;
        }

        info.innerHTML = html;
    }

});

</script>

@endsection