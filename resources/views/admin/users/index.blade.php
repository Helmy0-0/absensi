@extends('layouts.app')
@section('judul', 'Kelola User')
@section('isi')
<div class="card">
    <div class="flex between mb">
        <h2 style="margin:0">Daftar User</h2>
        <a class="btn" href="{{ route('admin.users.create') }}">+ Tambah User</a>
    </div>

    <form method="GET" class="filters">
        <div><label>Cari username / nama</label><input type="search" name="q" value="{{ request('q') }}" placeholder="Ketik lalu Enter"></div>
        <div>
            <label>Role</label>
            <select name="role">
                <option value="">Semua</option>
                @foreach (['admin' => 'Admin', 'kesiswaan' => 'Kesiswaan', 'guru' => 'Guru'] as $v => $l)
                    <option value="{{ $v }}" @selected(request('role') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="fix"><button class="btn">Filter</button> <a class="btn sec" href="{{ route('admin.users.index') }}">Reset</a></div>
    </form>

    <div class="tw">
    <table class="tbl">
        <thead><tr><th>No</th><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($users as $u)
            <tr>
                <td data-label="No">{{ $users->firstItem() + $loop->index }}</td>
                <td data-label="Nama">{{ $u->nama }}</td>
                <td data-label="Username">{{ $u->username }}</td>
                <td data-label="Email">{{ $u->email }}</td>
                <td data-label="Role"><x-badge :status="$u->role" /></td>
                <td data-label="Status">
                    <x-badge :status="$u->is_active ? 'aktif' : 'nonaktif'" />
                    @if ($u->role === 'guru' && $u->guru && $u->guru->status !== 'aktif')
                        <x-badge :status="$u->guru->status" />
                    @endif
                </td>
                <td class="act">
                    <a class="btn sm sec" href="{{ route('admin.users.edit', $u) }}">Edit</a>
                    @if ($u->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Hapus (nonaktifkan) user {{ $u->nama }}?')">
                        @csrf @method('DELETE')
                        <button class="btn sm bad">Hapus</button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $users->links() }}
</div>
@endsection
