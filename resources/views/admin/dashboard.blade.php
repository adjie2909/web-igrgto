@extends('layouts.admin')

@section('content')
<div class="page-stack">
    <section class="page-header">
        <div class="page-header__copy">
            <p class="eyebrow">Admin Overview</p>
            <h2 class="page-title">Admin Dashboard</h2>
            <p class="page-subtitle">Ringkasan cepat untuk data master dan aktivitas permintaan barang.</p>
        </div>
    </section>

    <section class="card">
        <p class="card-description">Selamat datang, <strong style="color:#111827;">{{ auth()->user()->name }}</strong></p>

        <div class="stats-grid" style="margin-top:1.25rem;">
            <div class="stat-card bg-total">
                <div class="stat-title">Total User</div>
                <div class="stat-value">{{ \App\Models\User::count() }}</div>
            </div>
            <div class="stat-card bg-proses">
                <div class="stat-title">Total Barang</div>
                <div class="stat-value">{{ \App\Models\Barang::count() }}</div>
            </div>
            <div class="stat-card bg-approved">
                <div class="stat-title">Total Request</div>
                <div class="stat-value">{{ \App\Models\RequestHeader::count() }}</div>
            </div>
        </div>
    </section>
</div>
@endsection
