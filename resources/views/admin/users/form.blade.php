@extends('layouts.app')
@section('judul', $user ? 'Edit User' : 'Tambah User')
@section('isi')
@php
    $edit = (bool) $user;
    $roleAwal = old('role', $user?->role ?? 'guru');
@endphp
<div class="card" style="max-width:640px" x-data="{ role: '{{ $roleAwal }}' }">
    <form method="POST" action="{{ $edit ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="fg"><label>Nama</label><input type="text" name="nama" value="{{ old('nama', $user?->nama) }}" required></div>

        <div class="grid g2">
            <div class="fg">
                <label>Username {{ $edit ? '(terkunci)' : '' }}</label>
                @if ($edit)
                    <input type="text" value="{{ $user->username }}" disabled>
                @else
                    <input type="text" name="username" value="{{ old('username') }}" required>
                @endif
            </div>
            <div class="fg">
                <label>Role {{ $edit ? '(terkunci)' : '' }}</label>
                @if ($edit)
                    <input type="text" value="{{ $user->labelRole() }}" disabled>
                @else
                    <select name="role" x-model="role" required>
                        <option value="admin">Admin</option>
                        <option value="kesiswaan">Kesiswaan</option>
                        <option value="guru">Guru</option>
                    </select>
                @endif
            </div>
        </div>

        <div class="grid g2">
            <div class="fg"><label>Email</label><input type="email" name="email" value="{{ old('email', $user?->email) }}" required></div>
            <div class="fg"><label>No. HP</label><input type="text" name="no_hp" value="{{ old('no_hp', $user?->no_hp) }}"></div>
        </div>

        <div class="fg" x-show="role === 'kesiswaan'" x-cloak>
            <label>Jabatan</label><input type="text" name="jabatan" value="{{ old('jabatan', $user?->jabatan) }}" placeholder="mis. Waka Kesiswaan">
        </div>

        <div class="grid g2" x-show="role === 'guru'" x-cloak>
            <div class="fg"><label>NUPTK</label><input type="text" name="nuptk" value="{{ old('nuptk', $user?->guru?->nuptk) }}"></div>
            <div class="fg">
                <label>Status Guru</label>
                <select name="status_guru">
                    @foreach (['aktif' => 'Aktif', 'cuti' => 'Cuti', 'nonaktif' => 'Nonaktif'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('status_guru', $user?->guru?->status ?? 'aktif') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="fg">
            <label>Password {{ $edit ? '(kosongkan jika tidak diubah — Admin dapat mereset)' : '(min. 8 karakter)' }}</label>
            <input type="password" name="password" autocomplete="new-password" {{ $edit ? '' : 'required' }}>
        </div>

        <div class="fg chk">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true))>
            <label for="is_active" style="margin:0">Akun aktif (dapat login)</label>
        </div>

        <button class="btn">Simpan</button>
        <a class="btn sec" href="{{ route('admin.users.index') }}">Batal</a>
    </form>
</div>
@endsection
