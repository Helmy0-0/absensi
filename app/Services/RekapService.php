<?php

namespace App\Services;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\Siswa;
use App\Support\Kalender;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekap resmi (R-41): hanya data `tervalidasi`.
 * Hari libur dan laporan `tidak_diabsen` yang belum diisi tidak dihitung.
 */
class RekapService
{
    /** @return Collection<int, array{kode:string,nama:string,hadir:int,sakit:int,izin:int,alpa:int}> */
    public function guru(Carbon $dari, Carbon $sampai): Collection
    {
        $hitung = AbsensiGuru::query()
            ->selectRaw('guru_id, status, COUNT(*) as total')
            ->where('status_validasi', 'tervalidasi')
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereRaw(Kalender::sqlBukanLibur('tanggal'))
            ->groupBy('guru_id', 'status')
            ->get()
            ->groupBy('guru_id');

        $guruList = Guru::withTrashed()
            ->with(['user' => fn ($q) => $q->withTrashed()])
            ->where(function ($q) use ($hitung) {
                $q->whereIn('id', $hitung->keys())
                    ->orWhere(fn ($w) => $w->where('status', 'aktif')->whereNull('deleted_at'));
            })
            ->get();

        return $guruList
            ->map(function (Guru $g) use ($hitung) {
                $per = ($hitung->get($g->id) ?? collect())->pluck('total', 'status');

                return [
                    'kode' => $g->nuptk ?: '-',
                    'nama' => $g->user?->nama ?? '(tanpa nama)',
                    'hadir' => (int) ($per['hadir'] ?? 0),
                    'sakit' => (int) ($per['sakit'] ?? 0),
                    'izin' => (int) ($per['izin'] ?? 0),
                    'alpa' => (int) ($per['alpa'] ?? 0),
                ];
            })
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @param  int|null  $kelasId  filter satu kelas (null = semua kelas)
     * @param  array|null  $batasKelasIds  pembatas kelas (untuk guru); null = tanpa batas
     * @return Collection<int, array{kode:string,nama:string,hadir:int,sakit:int,izin:int,alpa:int}>
     */
    public function siswa(Carbon $dari, Carbon $sampai, ?int $kelasId, ?array $batasKelasIds = null): Collection
    {
        $q = DB::table('absensi_siswa')
            ->join('absensi_kelas', 'absensi_kelas.id', '=', 'absensi_siswa.absensi_kelas_id')
            ->where('absensi_kelas.status_validasi', 'tervalidasi')
            ->whereBetween('absensi_kelas.tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereRaw(Kalender::sqlBukanLibur('absensi_kelas.tanggal'));

        if ($kelasId) {
            $q->where('absensi_kelas.kelas_id', $kelasId);
        } elseif ($batasKelasIds !== null) {
            $q->whereIn('absensi_kelas.kelas_id', $batasKelasIds);
        }

        $hitung = $q->selectRaw('absensi_siswa.siswa_id as siswa_id, absensi_siswa.status as status, COUNT(*) as total')
            ->groupBy('absensi_siswa.siswa_id', 'absensi_siswa.status')
            ->get()
            ->groupBy('siswa_id');

        $siswaList = Siswa::withTrashed()
            ->where(function ($w) use ($hitung, $kelasId) {
                $w->whereIn('id', $hitung->keys());
                if ($kelasId) {
                    // siswa aktif kelas tsb tetap tampil walau belum punya data
                    $w->orWhere(fn ($x) => $x->where('kelas_id', $kelasId)
                        ->where('status', 'aktif')
                        ->whereNull('deleted_at'));
                }
            })
            ->orderBy('nama')
            ->get();

        return $siswaList->map(function (Siswa $s) use ($hitung) {
            $per = ($hitung->get($s->id) ?? collect())->pluck('total', 'status');

            return [
                'kode' => $s->nis,
                'nama' => $s->nama,
                'hadir' => (int) ($per['hadir'] ?? 0),
                'sakit' => (int) ($per['sakit'] ?? 0),
                'izin' => (int) ($per['izin'] ?? 0),
                'alpa' => (int) ($per['alpa'] ?? 0),
            ];
        })->values();
    }
}
