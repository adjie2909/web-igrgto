@extends('layouts.app')

@section('content')
<div class="auth-shell">
    <div class="card auth-card">
        <div class="auth-brand">
            <div class="auth-brand__logo">
                <img src="{{ asset('assets/logo_indogrosir.png') }}" alt="Logo Indogrosir">
            </div>
            <div class="auth-brand__copy">
                <h2 class="auth-title">Login</h2>
                <p class="auth-subtitle">Portal internal Indogrosir Gorontalo.</p>
            </div>
        </div>

        @if($errors->any())
            <div id="errorPopup" class="auth-error">
                User ID atau Password salah
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label>User ID</label>
                <input type="text" name="userid" id="userid" class="input" maxlength="3" value="{{ old('userid') }}" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="password-field">
                    <input type="password" name="password" id="password" class="input password-input">
                    <button type="button" onclick="togglePassword('password', this)" class="password-toggle" aria-label="Tampilkan password" aria-pressed="false">
                        Show
                    </button>
                </div>
            </div>

            <div class="page-stack" style="gap:0.75rem; margin-top:1rem;">
                <button class="btn btn-primary" style="width:100%;" type="submit">Login</button>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-outline" style="width:100%;">Register</a>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
setTimeout(() => {
    const popup = document.getElementById('errorPopup');
    if (popup) {
        popup.style.display = 'none';
    }
}, 3000);
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const userid = document.getElementById('userid');
    if (userid) {
        userid.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }
});

function togglePassword(id, el) {
    const input = document.getElementById(id);

    if (input.type === 'password') {
        input.type = 'text';
        el.innerText = 'Hide';
        el.setAttribute('aria-label', 'Sembunyikan password');
        el.setAttribute('aria-pressed', 'true');
    } else {
        input.type = 'password';
        el.innerText = 'Show';
        el.setAttribute('aria-label', 'Tampilkan password');
        el.setAttribute('aria-pressed', 'false');
    }
}
</script>
@endsection
