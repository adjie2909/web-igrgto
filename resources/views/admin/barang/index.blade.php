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

    <table class="table-modern">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Fraction</th>
                <th>Unit</th>
                <th width="150">Aksi</th>
            </tr>
        </thead>

        <tbody>
            @foreach($barangs as $b)
            <tr>
                <td>{{ $b->kode_barang }}</td>
                <td>{{ $b->nama_barang }}</td>
                <td>{{ $b->fraction }}</td>
                <td>{{ $b->unit }}</td>
                <td>
                    <a href="{{ route('barang.edit', $b->id) }}" class="btn btn-blue">Edit</a>

                    <form action="{{ route('barang.destroy', $b->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-red" onclick="return confirm('Hapus barang?')">Hapus</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $barangs->links() }}

</div>

@endsection