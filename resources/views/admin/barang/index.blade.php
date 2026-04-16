@extends('layouts.admin')

@section('content')

<div class="card">

    <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
        <h2>Master Barang</h2>

        <form method="GET">
            <input type="text" name="search" placeholder="Cari barang..." class="input">
        </form>
    </div>

    <a href="{{ route('barang.create') }}" class="btn btn-blue" style="margin-bottom:10px;">
        + Tambah Barang
    </a>

    @if ($errors->any())
        <div style="margin-bottom:16px; padding:12px 14px; border-radius:10px; background:#fee2e2; color:#991b1b;">
            @if($errors->has('kode_barang'))
                <div>{{ $errors->first('kode_barang') }}</div>
            @else
                <div>{{ $errors->first() }}</div>
            @endif
        </div>
    @endif

    <table class="table-modern">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Fraction</th>
                <th>Unit</th>
                <th>Stok Saat Ini</th>
                <th width="90">Aksi</th>
            </tr>
        </thead>

        <tbody>
            @foreach($barangs as $b)
            <tr>
                <td>{{ $b->kode_barang }}</td>
                <td>{{ $b->nama_barang }}</td>
                <td>{{ $b->fraction }}</td>
                <td>{{ $b->unit }}</td>
                <td>{{ (int) ($b->stok_saat_ini ?? $b->stok ?? 0) }}</td>
                <td>
                    <details class="action-menu">
                        <summary class="action-menu__trigger">
                            <span></span><span></span><span></span>
                        </summary>
                        <div class="action-menu__panel">
                            <button
                                type="button"
                                class="action-menu__item js-edit-barang"
                                data-id="{{ $b->id }}"
                                data-kode="{{ $b->kode_barang }}"
                                data-nama="{{ $b->nama_barang }}"
                                data-fraction="{{ $b->fraction }}"
                                data-unit="{{ $b->unit }}"
                                data-stok="{{ $b->stok }}"
                                data-harga="{{ $b->harga_estimasi }}">
                                Edit
                            </button>

                            <form action="{{ route('barang.destroy', $b->id) }}" method="POST" class="action-menu__form action-menu__form--danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Hapus barang?')">Hapus</button>
                            </form>
                        </div>
                    </details>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $barangs->links() }}

</div>

<div id="editBarangModal" class="modal">
    <div class="modal-content" style="width:min(820px, 95vw);">
        <div style="margin-bottom:20px;">
            <h3 style="margin:0;">Edit Barang</h3>
            <p style="color:#64748b; font-size:14px; margin-top:6px;">
                Update data master barang
            </p>
        </div>

        <form method="POST" id="editBarangForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="edit_barang_id" id="edit_barang_id" value="{{ old('edit_barang_id') }}">

            <div class="grid-2">
                <div class="form-group">
                    <label>Kode Barang</label>
                    <input type="text" name="kode_barang" id="edit_kode_barang" class="input" value="{{ old('kode_barang') }}">
                </div>

                <div class="form-group">
                    <label>Fraction</label>
                    <input type="number" name="fraction" id="edit_fraction" class="input" value="{{ old('fraction') }}">
                </div>

                <div class="form-group full">
                    <label>Nama Barang</label>
                    <input type="text" name="nama_barang" id="edit_nama_barang" class="input" value="{{ old('nama_barang') }}">
                </div>

                <div class="form-group">
                    <label>Unit</label>
                    <input type="text" name="unit" id="edit_unit" class="input" value="{{ old('unit') }}">
                </div>

                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" id="edit_stok" class="input" value="{{ old('stok') }}">
                </div>

                <div class="form-group">
                    <label>Harga Estimasi</label>
                    <input type="number" name="harga_estimasi" id="edit_harga_estimasi" class="input" value="{{ old('harga_estimasi') }}">
                </div>
            </div>

            <div style="margin-top:25px; display:flex; justify-content:space-between; align-items:center;">
                <button type="button" class="btn btn-gray" onclick="closeBarangModal()">
                    Batal
                </button>

                <button class="btn btn-blue" type="submit">
                    Update Barang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const editBarangModal = document.getElementById('editBarangModal');
const editBarangForm = document.getElementById('editBarangForm');
const barangUpdateUrlTemplate = @json(route('barang.update', ['barang' => '__ID__']));

function openBarangModal(data) {
    document.getElementById('edit_barang_id').value = data.id ?? '';
    document.getElementById('edit_kode_barang').value = data.kode ?? '';
    document.getElementById('edit_nama_barang').value = data.nama ?? '';
    document.getElementById('edit_fraction').value = data.fraction ?? '';
    document.getElementById('edit_unit').value = data.unit ?? '';
    document.getElementById('edit_stok').value = data.stok ?? '';
    document.getElementById('edit_harga_estimasi').value = data.harga ?? '';

    editBarangForm.action = barangUpdateUrlTemplate.replace('__ID__', String(data.id ?? ''));
    editBarangModal.classList.add('show');
}

function closeBarangModal() {
    editBarangModal.classList.remove('show');
}

document.querySelectorAll('.js-edit-barang').forEach((button) => {
    button.addEventListener('click', function () {
        openBarangModal({
            id: this.dataset.id,
            kode: this.dataset.kode,
            nama: this.dataset.nama,
            fraction: this.dataset.fraction,
            unit: this.dataset.unit,
            stok: this.dataset.stok,
            harga: this.dataset.harga,
        });
    });
});

document.getElementById('edit_kode_barang').addEventListener('input', function () {
    this.value = this.value.toUpperCase();
});

editBarangModal.addEventListener('click', function (e) {
    if (e.target === this) {
        closeBarangModal();
    }
});

@if ($errors->any() && old('edit_barang_id'))
    openBarangModal({
        id: @json(old('edit_barang_id')),
        kode: @json(old('kode_barang')),
        nama: @json(old('nama_barang')),
        fraction: @json(old('fraction')),
        unit: @json(old('unit')),
        stok: @json(old('stok')),
        harga: @json(old('harga_estimasi')),
    });
@endif
</script>

@endsection
