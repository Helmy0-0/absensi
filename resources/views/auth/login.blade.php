@extends('layouts.guest')
@section('judul', 'Masuk')
@section('isi')
<div class="login-box">
    <h1>Absensi Terpadu</h1>
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
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <div class="fg chk">
            <input type="checkbox" id="ingat" name="ingat" value="1"> <label for="ingat" style="margin:0">Ingat saya</label>
        </div>
        <button class="btn" style="width:100%;justify-content:center">Masuk</button>
    </form>
</div>
@endsection
