@extends('layouts.admin')

@section('content')

<div class="card" style="max-width:700px; margin:auto;">

    <div style="margin-bottom:20px;">
        <h2>Edit User</h2>
        <p style="color:#64748b; font-size:14px;">
            Update informasi user dengan benar
        </p>
    </div>

    <form method="POST" action="{{ route('user.update', $user->id) }}">
        @csrf
        @method('PUT')

        <div class="grid-2">

            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="name" value="{{ $user->name }}">
            </div>

            <div class="form-group">
                <label>User ID</label>
                <input type="text" name="userid" value="{{ $user->userid }}">
            </div>

            <div class="form-group full">
                <label>Email</label>
                <input type="text" name="email" value="{{ $user->email }}">
            </div>

            <div class="form-group">
                <label>Divisi</label>
                <select name="division_id">
                    @foreach($divisions as $d)
                        <option value="{{ $d->id }}" {{ $user->division_id == $d->id ? 'selected' : '' }}>
                            {{ $d->nama_divisi }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    @foreach(['USER','SJM','SAM','SM','PGA','ADMIN'] as $r)
                        <option value="{{ $r }}" {{ $user->role == $r ? 'selected' : '' }}>
                            {{ $r }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- PASSWORD --}}
        <div style="margin-top:20px;">
            <div class="form-group">
                <label>Password (opsional)</label>
                <input type="password" name="password">
            </div>

            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="password_confirmation">
            </div>
        </div>

        <div style="margin-top:25px; display:flex; justify-content:space-between;">
            <a href="{{ route('user.index') }}" class="btn btn-gray">← Kembali</a>
            <button type="submit" class="btn btn-blue">Update User</button>
        </div>

    </form>

</div>

@endsection