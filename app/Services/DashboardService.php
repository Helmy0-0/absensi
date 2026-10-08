<?php

namespace App\Services;

use App\Models\AbsensiGuru;
use App\Models\AbsensiKelas;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Kalender;
use App\Support\Status;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function kartuAdmin(): array
    {
        return [
            ['label' => 'Total User', 'value' => User::where('is_active', true)->count()],
            ['label' => 'Total Guru', 'value' => Guru::aktif()->count()],
            ['label' => 'Total Siswa', 'value' => Siswa::where('status', 'aktif')->count()],
            ['label' => 'Total Kelas', 'value' => Kelas::tahunAktif()->count()],
        ];
    }

    /** R-43 */
    public function kartuKesiswaan(): array
    {
        $hariIni = Kalender::hariIni()->toDateString();

        $pendingGuru = AbsensiGuru::where('status_validasi', 'pending')->count();
        $pendingKelas = AbsensiKelas::where('status_validasi', 'pending')->count();
        $validGuru = AbsensiGuru::where('tanggal', $hariIni)->where('status_validasi', 'tervalidasi')->count();
        $validKelas = AbsensiKelas::where('tanggal', $hariIni)->where('status_validasi', 'tervalidasi')->count();

        return [
            ['label' => 'Total Guru Aktif', 'value' => Guru::aktif()->count()],
            ['label' => 'Menunggu Validasi', 'value' => "$pendingGuru guru · $pendingKelas kelas"],
            ['label' => 'Tervalidasi Hari Ini', 'value' => "$validGuru guru · $validKelas kelas"],
        ];
    }

    public function jumlahKelasTidakDiabsen(): int
    {
        return AbsensiKelas::where('status_validasi', 'tidak_diabsen')->count();
    }

    public function kartuGuru(Guru $guru): array
    {
        $kelasIds = $guru->kelasDiampu()->pluck('kelas.id')->all();
        $hariIni = Kalender::hariIni()->toDateString();

        $totalSiswa = $kelasIds
            ? Siswa::where('status', 'aktif')->whereIn('kelas_id', $kelasIds)->count()
            : 0;

        $hadir = 0;
        $total = 0;
        if ($kelasIds) {
            $baris = DB::table('absensi_siswa')
                ->join('absensi_kelas', 'absensi_kelas.id', '=', 'absensi_siswa.absensi_kelas_id')
                ->where('absensi_kelas.tanggal', $hariIni)
                ->whereIn('absensi_kelas.status_validasi', Status::DIHITUNG_DASHBOARD)
                ->whereIn('absensi_kelas.kelas_id', $kelasIds)
                ->selectRaw("COUNT(*) as total, COALESCE(SUM(CASE WHEN absensi_siswa.status = 'hadir' THEN 1 ELSE 0 END), 0) as hadir")
                ->first();
            $hadir = (int) ($baris->hadir ?? 0);
            $total = (int) ($baris->total ?? 0);
        }

        return [
            ['label' => 'Total Siswa (kelas diampu)', 'value' => $totalSiswa],
            ['label' => 'Siswa Hadir Hari Ini', 'value' => $hadir],
            ['label' => 'Siswa Tidak Hadir Hari Ini', 'value' => $total - $hadir, 'hint' => 'sakit + izin + alpa'],
        ];
    }

    /** Data yang `ditolak` dan perlu diperbaiki guru (R-33). */
    public function ditolakUntukGuru(Guru $guru): array
    {
        $absen = AbsensiGuru::where('guru_id', $guru->id)
            ->where('status_validasi', 'ditolak')
            ->orderByDesc('tanggal')
            ->get();

        $kelasIds = $guru->kelasDiampu(false)->pluck('kelas.id')->all();
        $laporan = $kelasIds
            ? AbsensiKelas::with('kelas')
                ->whereIn('kelas_id', $kelasIds)
                ->where('status_validasi', 'ditolak')
                ->orderByDesc('tanggal')
                ->get()
            : collect();

        return ['absen' => $absen, 'laporan' => $laporan];
    }

    /**
     * R-42: persentase kehadiran 7 hari efektif terakhir.
     *
     * @param  bool  $denganGuru  sertakan garis guru (admin/kesiswaan)
     * @param  array|null  $kelasIds  batasi siswa pada kelas tertentu (guru); null = seluruh sekolah
     */
    public function grafik(bool $denganGuru, ?array $kelasIds = null): array
    {
        $hari = Kalender::hariEfektifTerakhir(7);
        $awal = $hari[0]->toDateString();
        $akhir = end($hari)->toDateString();

        // --- siswa: hadir / total entri ---
        $siswa = collect();
        if ($kelasIds === null || $kelasIds) {
            $q = DB::table('absensi_siswa')
                ->join('absensi_kelas', 'absensi_kelas.id', '=', 'absensi_siswa.absensi_kelas_id')
                ->whereIn('absensi_kelas.status_validasi', Status::DIHITUNG_DASHBOARD)
                ->whereBetween('absensi_kelas.tanggal', [$awal, $akhir]);
            if ($kelasIds !== null) {
                $q->whereIn('absensi_kelas.kelas_id', $kelasIds);
            }
            $siswa = $q->groupBy('absensi_kelas.tanggal')
                ->selectRaw("absensi_kelas.tanggal as tgl, COUNT(*) as total, SUM(CASE WHEN absensi_siswa.status = 'hadir' THEN 1 ELSE 0 END) as hadir")
                ->get()
                ->keyBy('tgl');
        }

        // --- guru: hadir / total guru aktif ---
        $guru = collect();
        $totalGuru = 0;
        if ($denganGuru) {
            $totalGuru = Guru::aktif()->count();
            $guru = DB::table('absensi_guru')
                ->whereIn('status_validasi', Status::DIHITUNG_DASHBOARD)
                ->whereBetween('tanggal', [$awal, $akhir])
                ->groupBy('tanggal')
                ->selectRaw("tanggal as tgl, SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir")
                ->get()
                ->keyBy('tgl');
        }

        $labels = [];
        $seriSiswa = [];
        $seriGuru = [];
        foreach ($hari as $h) {
            $k = $h->toDateString();
            $labels[] = $h->copy()->locale('id')->translatedFormat('D d/m');

            $s = $siswa->get($k);
            $seriSiswa[] = $s && $s->total > 0 ? round($s->hadir / $s->total * 100, 1) : null;

            if ($denganGuru) {
                $g = $guru->get($k);
                $seriGuru[] = $g && $totalGuru > 0 ? round(min($g->hadir / $totalGuru, 1) * 100, 1) : null;
            }
        }

        return [
            'labels' => $labels,
            'siswa' => $seriSiswa,
            'guru' => $denganGuru ? $seriGuru : null,
        ];
    }
}
