<?php

namespace App\Services;

use App\Models\AbsensiGuru;
use App\Models\AbsensiKelas;
use App\Models\Guru;
use App\Models\Kelas;
use App\Support\Kalender;
use Carbon\Carbon;

class TutupHariService
{
    /**
     * Idempoten: bertumpu pada unique constraint + INSERT IGNORE sehingga aman
     * dijalankan berulang atau berbarengan dengan pengiriman form menit terakhir.
     */
    public function jalankan(Carbon $tanggal): array
    {
        $tgl = $tanggal->toDateString();
        $sekarang = Kalender::sekarang()->toDateTimeString();

        // R-14: guru aktif yang belum punya catatan → alpa (langsung tervalidasi).
        $guruIds = Guru::aktif()
            ->whereDoesntHave('absensi', fn ($q) => $q->where('tanggal', $tgl))
            ->pluck('guru.id');

        $barisGuru = $guruIds->map(fn ($id) => [
            'guru_id' => $id,
            'tanggal' => $tgl,
            'status' => 'alpa',
            'dibuat_sistem' => true,
            'status_validasi' => 'tervalidasi',
            'divalidasi_pada' => $sekarang,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ])->all();

        $guruAlpa = $barisGuru ? AbsensiGuru::insertOrIgnore($barisGuru) : 0;

        // R-25: kelas tahun ajaran aktif, ≥ 1 siswa aktif, belum ada laporan → tidak_diabsen.
        $kelasIds = Kelas::tahunAktif()
            ->whereHas('siswaAktif')
            ->whereDoesntHave('absensi', fn ($q) => $q->where('tanggal', $tgl))
            ->pluck('id');

        $barisKelas = $kelasIds->map(fn ($id) => [
            'kelas_id' => $id,
            'tanggal' => $tgl,
            'status_validasi' => 'tidak_diabsen',
            'diinput_manual' => false,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ])->all();

        $kelasTidak = $barisKelas ? AbsensiKelas::insertOrIgnore($barisKelas) : 0;

        return ['guru_alpa' => $guruAlpa, 'kelas_tidak_diabsen' => $kelasTidak];
    }
}
