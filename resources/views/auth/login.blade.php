@extends('layouts.guest')
@section('judul', 'Masuk')
@section('isi')
<div class="login-box">
    <h1>Presensi Terpadu</h1>
    <p class="tc mut" style="margin-top:0">{{ config('absensi.nama_sekolah') }}</p>

    @if ($errors->has('login'))
        <div class="alert alert-err">{{ $errors->first('login') }}</div>
    @endif

    <form method="POST" action="{{ route('login.proses') }}">
        @csrf
        <div class="fg">
            <label for="login">Username atau Email</label>
            <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username">
        </div>
        <div class="fg">
            <label for="password">Password</label>
            <div class="password-input">
                <input type="password" id="password" name="password" required autocomplete="current-password">
                <button type="button" class="password-visibility" data-password-toggle aria-controls="password" aria-label="Tampilkan password" aria-pressed="false">
                    <svg data-eye aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg data-eye-off aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" hidden><path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6.25 0 9.75 6 9.75 6a16.8 16.8 0 0 1-3.1 3.8M6.2 6.2C3.6 7.8 2.25 12 2.25 12s3.5 6 9.75 6a10.7 10.7 0 0 0 2.1-.2"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                </button>
            </div>
        </div>
        <div class="fg chk">
            <input type="checkbox" id="ingat" name="ingat" value="1"> <label for="ingat" style="margin:0">Ingat saya</label>
        </div>
        <button class="btn" style="width:100%;justify-content:center">Masuk</button>
    </form>
</div>
@endsection
