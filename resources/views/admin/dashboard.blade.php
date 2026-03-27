@extends('layouts.admin')

@section('content')

<div class="card">
    <h2>Admin Dashboard</h2>

    <p style="margin-top:10px;">
        Selamat datang, <b>{{ auth()->user()->name }}</b>
    </p>

    <div style="margin-top:20px; display:flex; gap:15px;">

        <div class="card" style="flex:1;">
            <h4>Total User</h4>
            <h2>{{ \App\Models\User::count() }}</h2>
        </div>

        <div class="card" style="flex:1;">
            <h4>Total Barang</h4>
            <h2>{{ \App\Models\Barang::count() }}</h2>
        </div>

        <div class="card" style="flex:1;">
            <h4>Total Request</h4>
            <h2>{{ \App\Models\RequestHeader::count() }}</h2>
        </div>

    </div>
</div>

@endsection