@extends('layouts.app')

@section('content')


    <div class="card" style="max-width:900px; margin:auto;">

        <h2 style="margin-bottom:20px;">Form Request Barang</h2>
        <p style="font-size:13px; color:#64748b;">
            Silakan isi kebutuhan barang dengan lengkap <br>
            Jika barang tidak tersedia didalam master barang, harap isi di keterangan
        </p>

        <form method="POST" action="{{ route('request.store') }}">
            @csrf

            <!-- TANGGAL -->
            <div style="margin-bottom:20px; max-width:300px; ">
                <label style="display:block; margin-bottom:5px;">Tanggal Request</label>
                <input type="date" name="tanggal_request" class="input">
            </div>

            <!-- TABLE -->
            <table id="table-barang">
                <tr>
                    <th>Barang</th>
                    <th style="width:100px;">Qty</th>
                    <th>Keterangan</th>
                    <th style="width:80px;">Aksi</th>
                </tr>

                <tr>
                    <td>
                        <select name="items[0][barang_id]" class="input">
                            <option value="">-- Pilih Barang --</option>
                            @foreach($barangs as $barang)
                                <option value="{{ $barang->id }}">{{ $barang->nama_barang }}</option>
                            @endforeach
                        </select>
                    </td>

                    <td>
                        <input type="number" name="items[0][qty]" class="input">
                    </td>

                    <td>
                        <input type="text" name="items[0][keterangan]" class="input">
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
                <select name="items[${index}][barang_id]" class="input">
                    <option value="">-- Pilih Barang --</option>
                    @foreach($barangs as $barang)
                        <option value="{{ $barang->id }}">{{ $barang->nama_barang }}</option>
                    @endforeach
                </select>
            </td>

            <td>
                <input type="number" name="items[${index}][qty]" class="input">
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

@endsection