@extends('layouts.admin')

@section('content')

<div class="card">

    <div class="header-modern">
        @if(session('success'))
        <div id="successPopup" style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: #22c55e;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            z-index: 9999;
        ">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div id="errorPopup" style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ef4444;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            z-index: 9999;
        ">
            {{ $errors->first('userid') ?: $errors->first() }}
        </div>
        @endif
        <div>
            <h2>Manajemen User</h2>
            <p>Kelola data user sistem</p>
        </div>

        <form method="GET" action="{{ route('user.index') }}">
            <input 
                type="text" 
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari user..."
                class="search-box"
                onkeyup="debounceSearch(this)"
            >
        </form>
    </div>

    <table class="table-modern">
        <thead>
            <tr>
                <th>Nama</th>
                <th>UserID</th>
                <th>Divisi</th>
                <th>Role</th>
                <th style="width:220px;">Aksi</th>
            </tr>
        </thead>

        <tbody>
        @foreach($users as $u)
            <tr>
                <td class="user-name" >{{ $u->name }}</td>
                <td>{{ $u->userid }}</td>
                <td>{{ $u->division->nama_divisi ?? '-' }}</td>

                <td>
                    <span class="badge badge-{{ strtolower($u->role) }}">
                        {{ $u->role }}
                    </span>
                </td>

                <td class="aksi">
                    <button class="btn btn-blue"
                        onclick="openEditModal(
                            '{{ $u->id }}',
                            '{{ $u->name }}',
                            '{{ $u->userid }}',
                            '{{ $u->email }}',
                            '{{ $u->division_id }}',
                            '{{ $u->role }}'
                        )">
                        Edit
                    </button>

                    <form action="{{ route('user.destroy', $u->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-red"
                            onclick="return confirm('Yakin hapus user?')">
                            Hapus
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div style="margin-top:15px;">
        <div class="pagination-custom">

            {{-- Previous --}}
            @if ($users->onFirstPage())
                <span class="disabled">«</span>
            @else
                <a href="{{ $users->previousPageUrl() }}">«</a>
            @endif

            {{-- Number --}}
            @for ($i = max(1, $users->currentPage()-2); $i <= min($users->lastPage(), $users->currentPage()+2); $i++)
                @if ($i == $users->currentPage())
                    <span class="active">{{ $i }}</span>
                @else
                    <a href="{{ $users->url($i) }}">{{ $i }}</a>
                @endif
            @endfor

            {{-- Next --}}
            @if ($users->hasMorePages())
                <a href="{{ $users->nextPageUrl() }}">»</a>
            @else
                <span class="disabled">»</span>
            @endif

        </div>
    </div>

</div>
<!-- MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">

        <h3>Edit User</h3>

        <form method="POST" id="editForm">
            @csrf
            @method('PUT')

            <div class="grid-2">

                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" name="name" id="edit_name">
                </div>

                <div class="form-group">
                    <label>User ID</label>
                    <input type="text" name="userid" id="edit_userid" maxlength="3">
                </div>

                <div class="form-group full">
                    <label>Email</label>
                    <input type="text" name="email" id="edit_email">
                </div>

                <div class="form-group">
                    <label>Divisi</label>
                    <select name="division_id" id="edit_division">
                        @foreach($divisions as $d)
                            <option value="{{ $d->id }}">{{ $d->nama_divisi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role">
                        @foreach(['USER','SJM','SAM','SM','PGA','ADMIN'] as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Password (opsional)</label>
                    <input type="password" name="password" id="edit_password">
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" id="edit_password_confirm">
                </div>

            </div>

            <div style="margin-top:15px;">
                <button type="submit" class="btn btn-blue">Update</button>
                <button type="button" class="btn btn-gray" onclick="closeModal()">Batal</button>
            </div>

        </form>

    </div>
</div>

<script>
// ==========================
// OPEN MODAL
// ==========================
function openEditModal(id, name, userid, email, division, role) {

    document.getElementById('edit_name').value = name;
    document.getElementById('edit_userid').value = userid;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_division').value = division;
    document.getElementById('edit_role').value = role;

    document.getElementById('editForm').action = '/admin/user/' + id;

    // 🔥 reset password setiap buka
    document.getElementById('edit_password').value = '';
    document.getElementById('edit_password_confirm').value = '';

    document.getElementById('editModal').style.display = 'flex';
}

// ==========================
// CLOSE MODAL
// ==========================
function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}


// ==========================
// VALIDASI SUBMIT
// ==========================
document.getElementById('editForm').addEventListener('submit', function(e){

    let pass = document.getElementById('edit_password').value;
    let confirm = document.getElementById('edit_password_confirm').value;

    if(pass !== '' && pass !== confirm){
        alert('Password dan konfirmasi password tidak sama');
        e.preventDefault();
    }

});


// ==========================
// REALTIME VALIDASI
// ==========================
document.addEventListener('input', function(){

    let pass = document.getElementById('edit_password');
    let confirm = document.getElementById('edit_password_confirm');

    if(confirm.value !== '' && pass.value !== confirm.value){
        confirm.style.border = '2px solid red';
    } else {
        confirm.style.border = '';
    }

    // Nama: setiap kata diawali huruf besar
    const name = document.getElementById('edit_name');
    if (name === document.activeElement) {
        name.value = name.value
            .toLowerCase()
            .replace(/\b\w/g, c => c.toUpperCase());
    }

    // User ID: maksimum 3 karakter + uppercase
    const userid = document.getElementById('edit_userid');
    if (userid === document.activeElement) {
        userid.value = userid.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 3);
    }

});
</script>


<script>
setTimeout(() => {
    let popup = document.getElementById('successPopup');
    if(popup){
        popup.style.opacity = '0';
        setTimeout(() => popup.remove(), 500);
    }
}, 2000);

setTimeout(() => {
    let popup = document.getElementById('errorPopup');
    if(popup){
        popup.style.opacity = '0';
        setTimeout(() => popup.remove(), 500);
    }
}, 3000);
</script>
@endsection
