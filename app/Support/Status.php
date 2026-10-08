<?php

namespace App\Support;

class Status
{
    public const KEHADIRAN = ['hadir', 'izin', 'sakit', 'alpa'];
    public const KEHADIRAN_GURU_MANDIRI = ['hadir', 'izin', 'sakit'];
    public const VALIDASI_GURU = ['pending', 'tervalidasi', 'ditolak'];
    public const VALIDASI_KELAS = ['pending', 'tervalidasi', 'ditolak', 'tidak_diabsen'];
    public const GURU = ['aktif', 'cuti', 'nonaktif'];
    public const SISWA = ['aktif', 'lulus', 'pindah', 'nonaktif'];
    public const PERAN = ['wali', 'guru_mapel'];
    public const ROLE = ['admin', 'kesiswaan', 'guru'];

    /** Status yang dihitung di dashboard/grafik (R-40) */
    public const DIHITUNG_DASHBOARD = ['pending', 'tervalidasi'];
}
