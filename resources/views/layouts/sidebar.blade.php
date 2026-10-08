@php
    $role = auth()->user()->role;

    $menu = match ($role) {
        'admin' => [
            ['Dashboard', route('admin.dashboard'), 'admin.dashboard'],
            ['Kelola User', route('admin.users.index'), 'admin.users.*'],
            ['Kelola Siswa', route('master.siswa.index'), 'master.siswa.*'],
            ['Kelola Kelas', route('master.kelas.index'), 'master.kelas.*|master.tahun-ajaran.*'],
            ['Relasi Guru–Kelas', route('master.relasi.index'), 'master.relasi.*'],
            ['Validasi Absen Guru', route('validasi.guru'), 'validasi.guru*'],
            ['Validasi Absen Siswa', route('validasi.siswa'), 'validasi.siswa*'],
            ['Rekap Absensi', route('rekap'), 'rekap*'],
            ['Profil Akun', route('profil'), 'profil*'],
        ],
        'kesiswaan' => [
            ['Dashboard', route('kesiswaan.dashboard'), 'kesiswaan.dashboard'],
            ['Validasi Absen Guru', route('validasi.guru'), 'validasi.guru*'],
            ['Validasi Absen Siswa', route('validasi.siswa'), 'validasi.siswa*'],
            ['Rekap Absensi', route('rekap'), 'rekap*'],
            ['Kelola Siswa', route('master.siswa.index'), 'master.siswa.*'],
            ['Kelola Kelas', route('master.kelas.index'), 'master.kelas.*|master.tahun-ajaran.*'],
            ['Relasi Guru–Kelas', route('master.relasi.index'), 'master.relasi.*'],
            ['Profil Akun', route('profil'), 'profil*'],
        ],
        default => [
            ['Dashboard', route('guru.dashboard'), 'guru.dashboard'],
            ['Absen Mandiri', route('guru.absen-mandiri'), 'guru.absen-mandiri*'],
            ['Absen Siswa', route('guru.absen-siswa'), 'guru.absen-siswa*'],
            ['Rekap Absensi', route('guru.rekap'), 'guru.rekap*'],
            ['Profil Akun', route('profil'), 'profil*'],
        ],
    };
@endphp
<nav class="sb-nav">
    @foreach ($menu as [$label, $url, $pola])
        <a href="{{ $url }}" class="{{ request()->routeIs(...explode('|', $pola)) ? 'on' : '' }}" @click="menu = false">{{ $label }}</a>
    @endforeach
</nav>
