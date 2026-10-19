@php
    $role = auth()->user()->role;

    $groups = match ($role) {
        'admin' => [
            'Ringkasan' => [['Dashboard', route('admin.dashboard'), 'admin.dashboard', 'home']],
            'Data sekolah' => [
                ['Kelola User', route('admin.users.index'), 'admin.users.*', 'users'],
                ['Kelola Siswa', route('master.siswa.index'), 'master.siswa.*', 'student'],
                ['Kelola Kelas', route('master.kelas.index'), 'master.kelas.*|master.tahun-ajaran.*', 'school'],
                ['Relasi Guru–Kelas', route('master.relasi.index'), 'master.relasi.*', 'link'],
            ],
            'Absensi & laporan' => [
                ['Validasi Absen Guru', route('validasi.guru'), 'validasi.guru*', 'check'],
                ['Validasi Absen Siswa', route('validasi.siswa'), 'validasi.siswa*', 'check'],
                ['Rekap Absensi', route('rekap'), 'rekap*', 'chart'],
                ['Monitoring Individu', route('monitoring-individu'), 'monitoring-individu*', 'chart'],
            ],
        ],
        'kesiswaan' => [
            'Ringkasan' => [['Dashboard', route('kesiswaan.dashboard'), 'kesiswaan.dashboard', 'home']],
            'Absensi & laporan' => [
                ['Validasi Absen Guru', route('validasi.guru'), 'validasi.guru*', 'check'],
                ['Validasi Absen Siswa', route('validasi.siswa'), 'validasi.siswa*', 'check'],
                ['Rekap Absensi', route('rekap'), 'rekap*', 'chart'],
                ['Monitoring Individu', route('monitoring-individu'), 'monitoring-individu*', 'chart'],
            ],
            'Data sekolah' => [
                ['Kelola Siswa', route('master.siswa.index'), 'master.siswa.*', 'student'],
                ['Kelola Kelas', route('master.kelas.index'), 'master.kelas.*|master.tahun-ajaran.*', 'school'],
                ['Relasi Guru–Kelas', route('master.relasi.index'), 'master.relasi.*', 'link'],
            ],
        ],
        default => [
            'Ringkasan' => [['Dashboard', route('guru.dashboard'), 'guru.dashboard', 'home']],
            'Kehadiran' => [
                ['Absen Mandiri', route('guru.absen-mandiri'), 'guru.absen-mandiri*', 'clock'],
                ['Absen Siswa', route('guru.absen-siswa'), 'guru.absen-siswa*', 'student'],
            ],
            'Laporan' => [
                ['Rekap Absensi', route('guru.rekap'), 'guru.rekap*', 'chart'],
                ['Monitoring Individu', route('guru.monitoring-individu'), 'guru.monitoring-individu*', 'chart'],
            ],
        ],
    };
@endphp
<nav class="sb-nav" aria-label="Navigasi utama">
    @foreach ($groups as $heading => $items)
        <section class="nav-group" aria-label="{{ $heading }}">
            <div class="nav-heading">{{ $heading }}</div>
            @foreach ($items as [$label, $url, $pola, $icon])
                <a href="{{ $url }}" @class(['on' => request()->routeIs(...explode('|', $pola))]) @if (request()->routeIs(...explode('|', $pola))) aria-current="page" @endif @click="menu = false">
                    <svg class="nav-icon" aria-hidden="true"><use href="#icon-{{ $icon }}"></use></svg>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </section>
    @endforeach
    <section class="nav-group" aria-label="Akun">
        <div class="nav-heading">Akun</div>
        <a href="{{ route('profil') }}" @class(['on' => request()->routeIs('profil*')]) @if (request()->routeIs('profil*')) aria-current="page" @endif @click="menu = false">
            <svg class="nav-icon" aria-hidden="true"><use href="#icon-user"></use></svg>
            <span>Profil Akun</span>
        </a>
    </section>
</nav>
