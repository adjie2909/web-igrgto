@extends('layouts.app')

@section('content')

<div style="display:flex; justify-content:center; align-items:center; min-height:100vh;">

    <div class="card" style="width:400px;">

        <div style="text-align:center; margin-bottom:20px;">
            <h2 style="margin-bottom:5px;">REGISTER</h2>
            <div style="font-size:13px; color:#64748b;">
                Buat akun baru
            </div>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- NAME -->
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="name" id="name" class="input" required>
            </div>

            <!-- USERID -->
            <div class="form-group">
                <label>User ID</label>
                <input type="text" name="userid" id="userid" class="input" maxlength="3" required>
            </div>

            <!-- DIVISI -->
            <div class="form-group">
                <label>Divisi</label>
                <select name="division_id" class="input" required>
                    <option value="">-- Pilih Divisi --</option>
                    @foreach($divisions as $d)
                        <option value="{{ $d->id }}">{{ $d->nama_divisi }}</option>
                    @endforeach
                </select>
            </div>

            <!-- EMAIL -->
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="input">
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

            <!-- CONFIRM -->
            <div class="form-group">
                <label>Confirm Password</label>
                <div style="position:relative;">
                    <input type="password" name="password_confirmation" id="password_confirmation" class="input">

                    <span onclick="togglePassword('password_confirmation', this)"
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
                                cursor:pointer; font-size:13px; color:#64748b;">
                        Show
                    </span>
                </div>
            </div>

            <!-- BUTTON -->
            <button class="btn btn-primary" style="width:100%;">
                Register
            </button>

            <div style="margin-top:15px; text-align:center; font-size:13px;">
                Sudah punya akun?
                <a href="{{ route('login') }}">Login</a>
            </div>

        </form>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // 🔥 USERID AUTO UPPERCASE
    const userid = document.getElementById('userid');
    if (userid) {
        userid.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }

    // 🔥 NAME AUTO KAPITAL
    const name = document.getElementById('name');
    if (name) {
        name.addEventListener('input', function () {
            this.value = this.value.replace(/\b\w/g, c => c.toUpperCase());
        });
    }

});

// 🔥 SHOW / HIDE PASSWORD
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

@endsection