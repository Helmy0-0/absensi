@props(['status'])
@php
    $peta = [
        'hadir' => ['Hadir', 'green'], 'izin' => ['Izin', 'blue'], 'sakit' => ['Sakit', 'yellow'], 'alpa' => ['Alpa', 'red'],
        'pending' => ['Menunggu', 'yellow'], 'tervalidasi' => ['Tervalidasi', 'green'], 'ditolak' => ['Ditolak', 'red'],
        'tidak_diabsen' => ['Tidak Diabsen', 'gray'],
        'aktif' => ['Aktif', 'green'], 'cuti' => ['Cuti', 'yellow'], 'nonaktif' => ['Nonaktif', 'gray'],
        'lulus' => ['Lulus', 'blue'], 'pindah' => ['Pindah', 'gray'],
        'wali' => ['Wali', 'purple'], 'guru_mapel' => ['Guru Mapel', 'blue'],
        'admin' => ['Admin', 'purple'], 'kesiswaan' => ['Kesiswaan', 'blue'], 'guru' => ['Guru', 'green'],
        'belum' => ['Belum absen', 'gray'],
    ];
    [$label, $warna] = $peta[$status] ?? [ucfirst(str_replace('_', ' ', $status)), 'gray'];
@endphp
<span class="badge badge-{{ $warna }}">{{ $label }}</span>
