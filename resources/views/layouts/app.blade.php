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
<body x-data="{ menu: false }" @keydown.escape.window="menu = false" :class="{ 'menu-open': menu }">
<svg aria-hidden="true" class="icon-library" xmlns="http://www.w3.org/2000/svg">
    <symbol id="icon-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/></symbol>
    <symbol id="icon-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 5v1h-3"/></symbol>
    <symbol id="icon-student" viewBox="0 0 24 24"><path d="m2 9 10-5 10 5-10 5zM6 11v5c3 3 9 3 12 0v-5M22 9v6"/></symbol>
    <symbol id="icon-school" viewBox="0 0 24 24"><path d="M3 21V8l9-5 9 5v13M1 21h22M8 21v-6h8v6M8 10h.01M12 10h.01M16 10h.01"/></symbol>
    <symbol id="icon-link" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.1 0l3-3A5 5 0 0 0 13 2.9l-1.7 1.7M14 11a5 5 0 0 0-7.1 0l-3 3A5 5 0 0 0 11 21.1l1.7-1.7"/></symbol>
    <symbol id="icon-check" viewBox="0 0 24 24"><path d="M9 11 12 14 22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></symbol>
    <symbol id="icon-chart" viewBox="0 0 24 24"><path d="M3 3v18h18M8 15l4-4 4 3 5-7"/></symbol>
    <symbol id="icon-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="icon-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol>
</svg>
<div class="app">
    <aside id="app-sidebar" class="sidebar" :class="{ open: menu }">
        <div class="sb-brand">
            <span class="sb-mark" aria-hidden="true">A</span>
            <span>Absensi Terpadu<small>{{ config('absensi.nama_sekolah') }}</small></span>
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
            <button type="button" class="burger" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="app-sidebar" aria-label="Buka navigasi">&#9776;</button>
            <h1>@yield('judul', 'Dashboard')</h1>
            <a href="{{ route('profil') }}" class="chip" aria-label="Profil {{ auth()->user()->nama }}">
                <span class="nm">{{ auth()->user()->nama }} · </span>{{ auth()->user()->labelRole() }}
            </a>
            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="btn sec sm">Keluar</button>
            </form>
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
