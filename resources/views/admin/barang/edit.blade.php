@extends('layouts.admin')

@section('content')

<div class="card" style="max-width:800px; margin:auto;">

    <div style="margin-bottom:20px;">
        <h2 style="margin:0;">Edit Barang</h2>
        <p style="color:#64748b; font-size:14px;">
            Update data master barang
        </p>
    </div>

    <form method="POST" action="{{ route('barang.update', $barang->id) }}">
        @csrf
        @method('PUT')

        <div class="grid-2">

            <div class="form-group">
                <label>Kode Barang</label>
                <input type="text" name="kode_barang" value="{{ $barang->kode_barang }}" class="input">
            </div>

            <div class="form-group">
                <label>Fraction</label>
                <input type="number" name="fraction" value="{{ $barang->fraction }}" class="input">
            </div>

            <div class="form-group full">
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" value="{{ $barang->nama_barang }}" class="input">
            </div>

            <div class="form-group">
                <label>Unit</label>
                <input type="text" name="unit" value="{{ $barang->unit }}" class="input">
            </div>

            <div class="form-group">
                <label>Stok</label>
                <input type="number" name="stok" value="{{ $barang->stok }}" class="input">
            </div>

            <div class="form-group">
                <label>Stok Tersedia</label>
                <input type="number" class="input" value="{{ (int) ($stokTersedia ?? 0) }}" readonly>
            </div>

            <div class="form-group">
                <label>Harga Estimasi</label>
                <input type="number" name="harga_estimasi" value="{{ $barang->harga_estimasi }}" class="input">
            </div>

        </div>

        <div style="margin-top:25px; display:flex; justify-content:space-between; align-items:center;">

            <a href="{{ route('barang.index') }}" class="btn btn-gray">
                Kembali
            </a>

            <button class="btn btn-blue">
                Update Barang
            </button>

        </div>

    </form>

</div>

@endsection
