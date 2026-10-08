@extends('layouts.app')
@section('judul', 'Profil Akun')
@section('isi')
@php $admin = $user->isAdmin(); @endphp
<div class="grid g2">
    <div class="card">
        <h2>Data Akun</h2>
        <table style="width:100%" class="sm">
            <tr><td class="mut" style="width:35%">Nama</td><td>{{ $user->nama }}</td></tr>
            <tr><td class="mut">Username</td><td>{{ $user->username }}</td></tr>
            <tr><td class="mut">Email</td><td>{{ $user->email }}</td></tr>
            <tr><td class="mut">No. Telepon</td><td>{{ $user->no_hp ?: '-' }}</td></tr>
            <tr><td class="mut">Role</td><td><x-badge :status="$user->role" /></td></tr>
            @if ($user->isKesiswaan())<tr><td class="mut">Jabatan</td><td>{{ $user->jabatan ?: '-' }}</td></tr>@endif
            @if ($user->isGuru())<tr><td class="mut">NUPTK</td><td>{{ $user->guru?->nuptk ?: '-' }}</td></tr>@endif
        </table>
    </div>

    <div class="card">
        <h2>Edit Profil</h2>
        @unless ($admin)
            <p class="mut sm">Anda hanya dapat mengubah email dan nomor HP. Perubahan data lain diajukan ke Admin.</p>
        @endunless
        <form method="POST" action="{{ route('profil.update') }}">
            @csrf @method('PUT')
            @if ($admin)
                <div class="fg"><label>Nama</label><input type="text" name="nama" value="{{ old('nama', $user->nama) }}" required></div>
                <div class="fg"><label>Username</label><input type="text" name="username" value="{{ old('username', $user->username) }}" required></div>
                <div class="fg"><label>Jabatan</label><input type="text" name="jabatan" value="{{ old('jabatan', $user->jabatan) }}"></div>
            @endif
            <div class="fg"><label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
            <div class="fg"><label>No. HP</label><input type="text" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}"></div>
            <button class="btn">Simpan Profil</button>
        </form>
    </div>
</div>

<div class="card" style="max-width:520px">
    <h2>Ganti Password</h2>
    @if (session('success_password'))<div class="alert alert-ok">{{ session('success_password') }}</div>@endif
    <form method="POST" action="{{ route('profil.password') }}">
        @csrf @method('PUT')
        <div class="fg"><label>Password lama</label><input type="password" name="password_lama" required autocomplete="current-password"></div>
        <div class="fg"><label>Password baru (min. 8 karakter)</label><input type="password" name="password" required autocomplete="new-password"></div>
        <div class="fg"><label>Ulangi password baru</label><input type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <button class="btn">Ganti Password</button>
    </form>
</div>
@endsection
