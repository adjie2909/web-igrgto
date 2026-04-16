<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>IGR GTO</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo_indogrosir.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="app-shell">
        @if(!request()->is('login') && !request()->is('register'))
            <header class="app-nav">
                <div class="app-nav__inner">
                    <div class="app-nav__top">
                        <div class="brand">
                            <div class="brand__mark">
                                <img src="{{ asset('assets/logo_indogrosir.png') }}" alt="Logo Indogrosir">
                            </div>
                            <div>
                                <div class="brand__title">Indogrosir Gorontalo</div>
                                <div class="brand__subtitle">Internal request and ticketing portal</div>
                            </div>
                        </div>

                        <nav class="nav-menu">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->is('dashboard*') || request()->is('request') || request()->is('request/*') ? 'active' : '' }}">
                                Permintaan Barang
                            </a>

                            @auth
                                @if(!in_array(auth()->user()->role, ['SJM', 'SAM', 'SM']))
                                    <a href="{{ route('request-claim.index') }}"
                                        class="nav-link {{ request()->is('request-claim*') ? 'active' : '' }}">
                                        Pengambilan Barang
                                    </a>
                                @endif
                            @endauth

                            <a href="{{ route('ticket.index') }}"
                                class="nav-link {{ request()->is('ticket*') ? 'active' : '' }}">
                                Ticketing Case
                            </a>
                        </nav>

                        <div class="nav-user">
                            @auth
                                <div class="nav-user__meta">
                                    <strong>{{ auth()->user()->userid }}</strong>
                                    <!-- <div class="nav-user__subline">
                                        <span>{{ auth()->user()->userid }}</span>
                                        <span class="nav-user__separator" aria-hidden="true"></span>
                                        <span>{{ auth()->user()->role }}</span>
                                    </div> -->
                                </div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="btn btn-outline" type="submit">Logout</button>
                                </form>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>
        @endif

        <main class="page-shell">
            @yield('content')
        </main>
    </div>

    @if(session('success'))
        <div id="modalSuccess" class="modal modal-success">
            <div class="modal-content modal-content--success success-content">
                <div class="success-icon" aria-hidden="true"></div>
                <h3 class="success-title">Berhasil</h3>
                <p class="success-message">
                    {{ session('success') }}
                </p>
                <button class="btn btn-primary" id="btnCloseSuccess" type="button">
                    OK
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div id="modalError" class="modal modal-success">
            <div class="modal-content modal-content--success success-content">
                <div class="error-icon" aria-hidden="true">!</div>
                <h3 class="success-title">Peringatan</h3>
                <p class="success-message">
                    {{ session('error') }}
                </p>
                <button class="btn btn-primary" id="btnCloseError" type="button">
                    OK
                </button>
            </div>
        </div>
    @endif
</body>
</html>
<script>
document.addEventListener('DOMContentLoaded', function () {

    @if(session('success'))

        const modal = document.getElementById('modalSuccess');
        const btn = document.getElementById('btnCloseSuccess');

        if(modal){
            modal.classList.add('show');

            if(btn){
                btn.onclick = function(){
                    modal.classList.remove('show');
                }
            }

            setTimeout(() => {
                modal.classList.remove('show');
            }, 3000);
        }

    @endif

    @if(session('error'))

        const modalError = document.getElementById('modalError');
        const btnError = document.getElementById('btnCloseError');

        if(modalError){
            modalError.classList.add('show');

            if(btnError){
                btnError.onclick = function(){
                    modalError.classList.remove('show');
                }
            }

            setTimeout(() => {
                modalError.classList.remove('show');
            }, 4000);
        }

    @endif

});
</script>
