@extends('layouts.admin')

@section('content')

<div class="card">

    <div class="header-modern">
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
                    <a href="{{ route('user.reset', $u->id) }}"
                       class="btn btn-gray"
                       onclick="return confirm('Reset password ke 123456?')">
                        Reset
                    </a>

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
                    <input type="text" name="userid" id="edit_userid">
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

            </div>

            <div style="margin-top:15px;">
                <button class="btn btn-blue">Update</button>
                <button type="button" class="btn btn-gray" onclick="closeModal()">Batal</button>
            </div>

        </form>

    </div>
</div>

<script>
function openEditModal(id, name, userid, email, division, role) {

    document.getElementById('edit_name').value = name;
    document.getElementById('edit_userid').value = userid;
    document.getElementById('edit_email').value = email;

    document.getElementById('edit_division').value = division;
    document.getElementById('edit_role').value = role;

    document.getElementById('editForm').action = '/admin/user/' + id;

    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>
<script>
let timer;
function debounceSearch(el) {
    clearTimeout(timer);
    timer = setTimeout(() => {
        el.form.submit();
    }, 500);
}
</script>
@endsection