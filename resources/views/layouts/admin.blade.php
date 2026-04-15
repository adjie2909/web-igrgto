<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo_indogrosir.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    @php
        $user = auth()->user();
        $isAdminDashboard = request()->routeIs('dashboard');
        $isUserPage = request()->routeIs('user.*');
        $isRequestPage = request()->routeIs('request.*');
        $isClaimPage = request()->routeIs('request-claim.*');
        $isBarangPage = request()->routeIs('barang.*');
    @endphp

    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-sidebar__brand">
                <div class="admin-sidebar__title">Admin Panel</div>
                <div class="admin-sidebar__subtitle">Kontrol data master dan operasional internal</div>
            </div>

            <nav class="admin-menu">
                @if($user->role == 'ADMIN' && $user->userid != 'PGA')
                    <a href="{{ route('dashboard') }}" class="{{ $isAdminDashboard ? 'is-active' : '' }}">Dashboard</a>
                    <a href="{{ route('user.index') }}" class="{{ $isUserPage ? 'is-active' : '' }}">User</a>
                    <a href="{{ route('request.index') }}" class="{{ $isRequestPage ? 'is-active' : '' }}">Request</a>
                @endif

                @if($user->role == 'PGA' || $user->userid == 'PGA')
                    <a href="{{ route('request-claim.index') }}" class="{{ $isClaimPage ? 'is-active' : '' }}">Permintaan Barang Masuk</a>
                @endif

                @if($user->role == 'ADMIN')
                    <a href="{{ route('barang.index') }}" class="{{ $isBarangPage ? 'is-active' : '' }}">Barang</a>
                @endif
            </nav>

            <div class="admin-sidebar__footer">
                <div class="nav-user__meta admin-user-card">
                    <strong>{{ $user->name }}</strong>
                    <div class="nav-user__subline">
                        <span>{{ $user->userid }}</span>
                        <span class="nav-user__separator" aria-hidden="true"></span>
                        <span>{{ $user->role }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline" type="submit" style="width:100%; background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.08); color:#fff;">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="admin-main">
            <div class="content">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
