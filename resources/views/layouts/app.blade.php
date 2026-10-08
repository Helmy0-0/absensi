<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Dashboard') – Absensi {{ config('absensi.nama_sekolah') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('head')
</head>
<body x-data="{ menu: false }">
<div class="app">
    <aside class="sidebar" :class="{ open: menu }">
        <div class="sb-brand">
            Absensi Terpadu
            <small>{{ config('absensi.nama_sekolah') }}</small>
        </div>
        @include('layouts.sidebar')
        <div class="sb-foot">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn sec" style="width:100%;justify-content:center">Keluar</button>
            </form>
        </div>
    </aside>
    <div class="overlay" x-show="menu" x-cloak @click="menu = false"></div>

    <div class="main">
        <header class="topbar">
            <button class="burger" @click="menu = !menu" aria-label="Menu">&#9776;</button>
            <h1>@yield('judul', 'Dashboard')</h1>
            <a href="{{ route('profil') }}" class="chip">
                <span class="nm">{{ auth()->user()->nama }} · </span>{{ auth()->user()->labelRole() }}
            </a>
        </header>
        <main class="content">
            @include('layouts.alerts')
            @yield('isi')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
