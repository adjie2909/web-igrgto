@extends('layouts.admin')

@section('content')

    <div class="card" style="max-width:700px; margin:auto;">

        <div style="margin-bottom:20px;">
            <h2 style="margin:0;">Tambah Barang</h2>
            <p style="color:#64748b; font-size:14px;">
                Tambahkan master barang baru
            </p>
        </div>
        @if ($errors->any())
            <div style="margin-bottom:16px; padding:12px 14px; border-radius:10px; background:#fee2e2; color:#991b1b;">
                @if($errors->has('kode_barang'))
                    <div>{{ $errors->first('kode_barang') }}</div>
                @else
                    <div>{{ $errors->first() }}</div>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('barang.store') }}">
            @csrf

            <div class="grid-2">

                {{-- KODE --}}
                <div class="form-group">
                    <label>Kode Barang</label>
                    <input type="text" name="kode_barang" class="input" placeholder="BRG001"
                        value="{{ old('kode_barang') }}">
                </div>

                {{-- FRACTION --}}
                <div class="form-group">
                    <label>Fraction</label>
                    <input type="number" name="fraction" class="input" value="{{ old('fraction', 1) }}">
                </div>

                {{-- NAMA --}}
                <div class="form-group full">
                    <label>Nama Barang</label>
                    <input type="text" name="nama_barang" class="input" placeholder="Contoh: Pulpen"
                        value="{{ old('nama_barang') }}">
                </div>

                {{-- UNIT --}}
                <div class="form-group full">
                    <label>Unit</label>
                    <input type="text" name="unit" class="input" placeholder="pcs / box / pack"
                        value="{{ old('unit') }}">
                </div>

                {{-- STOK --}}
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" class="input" value="{{ old('stok', 0) }}">
                </div>

                {{-- HARGA --}}
                <div class="form-group">
                    <label>Harga Estimasi</label>
                    <input type="number" name="harga_estimasi" class="input" placeholder="Contoh: 5000"
                        value="{{ old('harga_estimasi') }}">
                </div>

            </div>

            <div style="margin-top:25px; display:flex; justify-content:space-between; align-items:center;">

                <a href="{{ route('barang.index') }}" class="btn btn-gray">
                    Kembali
                </a>

                <button class="btn btn-blue">
                    Simpan Barang
                </button>

            </div>

        </form>

    </div>
<script>
document.querySelector('[name="kode_barang"]').addEventListener('input', function(){
    this.value = this.value.toUpperCase();
});
</script>
@endsection
