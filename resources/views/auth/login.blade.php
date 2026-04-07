@extends('layouts.app')

@section('content')

<div style="display:flex; justify-content:center; align-items:center; min-height:100vh;">

    <div class="card" style="width:360px;">

        <div style="display:flex; justify-content:center; align-items:center; gap:6px; margin-bottom:18px;">
            <div style="width:116px; height:46px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                <img src="{{ asset('assets/logo_indogrosir.png') }}" alt="Logo Indogrosir" style="width:125%; height:125%; object-fit:cover; object-position:center; display:block;">
            </div>
            <h2 style="margin:0; font-size:26px; line-height:1;">LOGIN</h2>
        </div>

        {{-- 🔥 POPUP ERROR --}}
        @if($errors->any())
        <div id="errorPopup" style="
            background:#fee2e2;
            color:#991b1b;
            padding:10px;
            border-radius:8px;
            margin-bottom:15px;
            text-align:center;
        ">
            User ID atau Password salah
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- USERID -->
            <div class="form-group">
                <label>User ID</label>

                <input 
                    type="text" 
                    name="userid" 
                    id="userid" 
                    class="input" 
                    maxlength="3" 
                    value="{{ old('userid') }}" 
                    required>


            </div>

            <!-- PASSWORD -->
            <div class="form-group">
                <label>Password</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="password" class="input">

                    <span onclick="togglePassword('password', this)"
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
                                cursor:pointer; font-size:13px; color:#64748b;">
                        Show
                    </span>
                </div>
            </div>

            <!-- REMEMBER
            <div style="margin-bottom:15px; font-size:13px;">
                <input type="checkbox" name="remember"> Remember me
            </div> -->

            <!-- BUTTON -->
            <button class="btn btn-primary" style="width:100%; margin-top:5px;">
                Login
            </button>

            @if (Route::has('register'))
            <a href="{{ route('register') }}" class="btn btn-outline" style="display:block; width:100%; box-sizing:border-box; margin-top:10px; text-align:center;">
                Register
            </a>
            @endif

        </form>

    </div>

</div>

{{-- 🔥 AUTO HILANG POPUP --}}
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

// SHOW / HIDE PASSWORD
function togglePassword(id, el) {
    const input = document.getElementById(id);

    if (input.type === "password") {
        input.type = "text";
        el.innerText = "Hide";
    } else {
        input.type = "password";
        el.innerText = "Show";
    }
}
</script>
<!-- @if ($errors->has('userid'))
<script>
    alert("{{ $errors->first('userid') }}");
</script>
@endif -->
@endsection
