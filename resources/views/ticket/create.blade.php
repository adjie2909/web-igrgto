@extends('layouts.app')

@section('content')

<div class="card">

    <h2>Buat Ticket</h2>

    <form method="POST" action="{{ route('ticket.store') }}">
        @csrf

        <div class="form-group">
            <label>Judul</label>
            <input type="text" name="judul" class="input">
        </div>

        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="input"></textarea>
        </div>

        <button class="btn btn-blue">Kirim</button>

    </form>

</div>

@endsection