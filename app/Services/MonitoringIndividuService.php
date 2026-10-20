<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;
use App\Support\Kalender;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonitoringIndividuService
{
    private const STATUS = ['hadir', 'sakit', 'izin', 'alpa'];

    /**
     * @return array{status:array<string,int>,total:int,hadir_percent:?float,hari_tercatat:int,trend:array<int,array{label:string,percent:?float,total:int,hadir:int}>,harian:array<int,array{tanggal:string,label:string,status:array<string,int>,total:int,percent:?float}>}
     */
    public function siswa(Siswa $siswa, Carbon $dari, Carbon $sampai, string $skalaTren = 'minggu'): array
    {
        $tidakHadir = DB::table('absensi_siswa')
            ->join('absensi_kelas', 'absensi_kelas.id', '=', 'absensi_siswa.absensi_kelas_id')
            ->where('absensi_siswa.siswa_id', $siswa->id)
            ->where('absensi_kelas.status_validasi', 'tervalidasi')
            ->whereIn('absensi_siswa.status', ['sakit', 'izin', 'alpa'])
            ->whereBetween('absensi_kelas.tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereRaw(Kalender::sqlBukanLibur('absensi_kelas.tanggal'))
            ->orderByDesc('absensi_kelas.tanggal')
            ->selectRaw('absensi_kelas.tanggal as tanggal, absensi_siswa.status as status, absensi_siswa.keterangan as alasan')
            ->get();

        $rows = DB::table('absensi_siswa')
            ->join('absensi_kelas', 'absensi_kelas.id', '=', 'absensi_siswa.absensi_kelas_id')
            ->where('absensi_siswa.siswa_id', $siswa->id)
            ->where('absensi_kelas.status_validasi', 'tervalidasi')
            ->whereBetween('absensi_kelas.tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereRaw(Kalender::sqlBukanLibur('absensi_kelas.tanggal'))
            ->groupBy('absensi_kelas.tanggal', 'absensi_siswa.status')
            ->orderBy('absensi_kelas.tanggal')
            ->selectRaw('absensi_kelas.tanggal as tanggal, absensi_siswa.status as status, COUNT(*) as jumlah')
            ->get();

        return $this->ringkasan($rows, $dari, $sampai, $skalaTren) + [
            'ketidakhadiran' => $this->formatKetidakhadiran($tidakHadir),
        ];
    }

    /**
     * @return array{status:array<string,int>,total:int,hadir_percent:?float,hari_tercatat:int,trend:array<int,array{label:string,percent:?float,total:int,hadir:int}>,harian:array<int,array{tanggal:string,label:string,status:array<string,int>,total:int,percent:?float}>}
     */
    public function guru(Guru $guru, Carbon $dari, Carbon $sampai, string $skalaTren = 'minggu'): array
    {
        $tidakHadir = DB::table('absensi_guru')
            ->where('guru_id', $guru->id)
            ->where('status_validasi', 'tervalidasi')
            ->whereIn('status', ['sakit', 'izin', 'alpa'])
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereRaw(Kalender::sqlBukanLibur('tanggal'))
            ->orderByDesc('tanggal')
            ->selectRaw('tanggal, status, alasan')
            ->get();

        $rows = DB::table('absensi_guru')
            ->where('guru_id', $guru->id)
            ->where('status_validasi', 'tervalidasi')
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereRaw(Kalender::sqlBukanLibur('tanggal'))
            ->groupBy('tanggal', 'status')
            ->orderBy('tanggal')
            ->selectRaw('tanggal, status, COUNT(*) as jumlah')
            ->get();

        return $this->ringkasan($rows, $dari, $sampai, $skalaTren) + [
            'ketidakhadiran' => $this->formatKetidakhadiran($tidakHadir),
        ];
    }

    /** @return array<int,array{label:string,percent:?float,total:int,hadir:int}> */
    public function trenSiswa(Siswa $siswa, string $skala, Carbon $sampai): array
    {
        [$dari, $sampai] = $this->rentangTren($skala, $sampai);
        $rows = DB::table('absensi_siswa')
            ->join('absensi_kelas', 'absensi_kelas.id', '=', 'absensi_siswa.absensi_kelas_id')
            ->where('absensi_siswa.siswa_id', $siswa->id)
            ->where('absensi_kelas.status_validasi', 'tervalidasi')
            ->whereBetween('absensi_kelas.tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereRaw(Kalender::sqlBukanLibur('absensi_kelas.tanggal'))
            ->groupBy('absensi_kelas.tanggal', 'absensi_siswa.status')
            ->selectRaw('absensi_kelas.tanggal as tanggal, absensi_siswa.status as status, COUNT(*) as jumlah')
            ->get();

        return $this->susunTren($rows, $skala, $dari, $sampai);
    }

    /** @return array<int,array{label:string,percent:?float,total:int,hadir:int}> */
    public function trenGuru(Guru $guru, string $skala, Carbon $sampai): array
    {
        [$dari, $sampai] = $this->rentangTren($skala, $sampai);
        $rows = DB::table('absensi_guru')
            ->where('guru_id', $guru->id)
            ->where('status_validasi', 'tervalidasi')
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereRaw(Kalender::sqlBukanLibur('tanggal'))
            ->groupBy('tanggal', 'status')
            ->selectRaw('tanggal, status, COUNT(*) as jumlah')
            ->get();

        return $this->susunTren($rows, $skala, $dari, $sampai);
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function rentangTren(string $skala, Carbon $sampai): array
    {
        $jumlah = ['minggu' => 8, 'bulan' => 12, 'semester' => 6, 'tahun' => 5][$skala];
        $awal = $this->mulaiPeriodeTren($sampai, $skala);

        return match ($skala) {
            'minggu' => [$awal->subWeeks($jumlah - 1), $sampai->copy()],
            'bulan' => [$awal->subMonthsNoOverflow($jumlah - 1), $sampai->copy()],
            'semester' => [$awal->subMonthsNoOverflow(6 * ($jumlah - 1)), $sampai->copy()],
            'tahun' => [$awal->subYears($jumlah - 1), $sampai->copy()],
        };
    }

    /** @param Collection<int,object> $rows
     * @return array<int,array{label:string,percent:?float,total:int,hadir:int}>
     */
    private function susunTren(Collection $rows, string $skala, Carbon $dari, Carbon $sampai): array
    {
        $perPeriode = [];
        foreach ($rows as $row) {
            if (! in_array($row->status, self::STATUS, true)) {
                continue;
            }

            $tanggal = Carbon::parse($row->tanggal, Kalender::tz());
            $key = $this->kunciPeriodeTren($tanggal, $skala);
            $perPeriode[$key] ??= array_fill_keys(self::STATUS, 0);
            $perPeriode[$key][$row->status] += (int) $row->jumlah;
        }

        $trend = [];
        $cursor = $this->mulaiPeriodeTren($dari, $skala);
        while ($cursor->lte($sampai)) {
            $status = $perPeriode[$this->kunciPeriodeTren($cursor, $skala)] ?? array_fill_keys(self::STATUS, 0);
            $total = array_sum($status);
            $trend[] = [
                'label' => $this->labelPeriodeTren($cursor, $skala, $dari, $sampai),
                'percent' => $total > 0 ? round($status['hadir'] / $total * 100, 1) : null,
                'total' => $total,
                'hadir' => $status['hadir'],
            ];
            $cursor = $this->periodeTrenBerikutnya($cursor, $skala);
        }

        return $trend;
    }

    /** @param Collection<int,object> $rows
     * @return array<int,array{tanggal:string,hari:string,status:string,alasan:?string}>
     */
    private function formatKetidakhadiran(Collection $rows): array
    {
        return $rows->map(function ($row) {
            $tanggal = Carbon::parse($row->tanggal, Kalender::tz());

            return [
                'tanggal' => $tanggal->format('d/m/Y'),
                'hari' => $tanggal->locale('id')->translatedFormat('l'),
                'status' => $row->status,
                'alasan' => filled($row->alasan) ? $row->alasan : null,
            ];
        })->all();
    }

    /**
     * @param  Collection<int,object>  $rows
     * @return array{status:array<string,int>,total:int,hadir_percent:?float,hari_tercatat:int,trend:array<int,array{label:string,percent:?float,total:int,hadir:int}>,harian:array<int,array{tanggal:string,label:string,status:array<string,int>,total:int,percent:?float}>}
     */
    private function ringkasan(Collection $rows, Carbon $dari, Carbon $sampai, string $skalaTren): array
    {
        $totals = array_fill_keys(self::STATUS, 0);
        $byDate = [];

        foreach ($rows as $row) {
            if (! array_key_exists($row->status, $totals)) {
                continue;
            }

            $date = Carbon::parse($row->tanggal, Kalender::tz());
            $key = $date->toDateString();
            $jumlah = (int) $row->jumlah;
            $totals[$row->status] += $jumlah;
            $byDate[$key] ??= array_fill_keys(self::STATUS, 0);
            $byDate[$key][$row->status] += $jumlah;
        }

        $harian = [];
        $perPeriode = [];

        foreach ($byDate as $key => $status) {
            $date = Carbon::parse($key, Kalender::tz());
            $total = array_sum($status);
            $percent = $total > 0 ? round($status['hadir'] / $total * 100, 1) : null;
            $harian[] = [
                'tanggal' => $key,
                'label' => $date->locale('id')->translatedFormat('D, d M Y'),
                'status' => $status,
                'total' => $total,
                'percent' => $percent,
            ];

            $bucketKey = $this->kunciPeriodeTren($date, $skalaTren);
            $perPeriode[$bucketKey] ??= array_fill_keys(self::STATUS, 0);
            foreach (self::STATUS as $statusName) {
                $perPeriode[$bucketKey][$statusName] += $status[$statusName];
            }
        }

        $trend = [];
        $cursor = $this->mulaiPeriodeTren($dari, $skalaTren);
        while ($cursor->lte($sampai)) {
            $key = $this->kunciPeriodeTren($cursor, $skalaTren);
            $status = $perPeriode[$key] ?? array_fill_keys(self::STATUS, 0);
            $total = array_sum($status);
            $trend[] = [
                'label' => $this->labelPeriodeTren($cursor, $skalaTren, $dari, $sampai),
                'percent' => $total > 0 ? round($status['hadir'] / $total * 100, 1) : null,
                'total' => $total,
                'hadir' => $status['hadir'],
            ];
            $cursor = $this->periodeTrenBerikutnya($cursor, $skalaTren);
        }

        $total = array_sum($totals);

        return [
            'status' => $totals,
            'total' => $total,
            'hadir_percent' => $total > 0 ? round($totals['hadir'] / $total * 100, 1) : null,
            'hari_tercatat' => count($byDate),
            'trend' => $trend,
            'harian' => array_values($harian),
        ];
    }

    private function kunciPeriodeTren(Carbon $tanggal, string $skala): string
    {
        return match ($skala) {
            'bulan' => $tanggal->format('Y-m'),
            'semester' => ($tanggal->month >= 7 ? '1-' : '2-').$tanggal->year,
            'tahun' => $tanggal->format('Y'),
            default => $tanggal->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
        };
    }

    private function mulaiPeriodeTren(Carbon $tanggal, string $skala): Carbon
    {
        return match ($skala) {
            'bulan' => $tanggal->copy()->startOfMonth(),
            'semester' => Carbon::create(
                $tanggal->year,
                $tanggal->month >= 7 ? 7 : 1,
                1,
                0,
                0,
                0,
                Kalender::tz()
            ),
            'tahun' => $tanggal->copy()->startOfYear(),
            default => $tanggal->copy()->startOfWeek(Carbon::MONDAY),
        };
    }

    private function periodeTrenBerikutnya(Carbon $tanggal, string $skala): Carbon
    {
        return match ($skala) {
            'bulan' => $tanggal->copy()->addMonthNoOverflow(),
            'semester' => $tanggal->copy()->addMonthsNoOverflow(6),
            'tahun' => $tanggal->copy()->addYear(),
            default => $tanggal->copy()->addWeek(),
        };
    }

    private function labelPeriodeTren(Carbon $tanggal, string $skala, Carbon $dari, Carbon $sampai): string
    {
        if ($skala === 'minggu') {
            $mulai = $tanggal->copy()->startOfWeek(Carbon::MONDAY);
            $akhir = $tanggal->copy()->endOfWeek(Carbon::SUNDAY);
            $mulai = $mulai->lt($dari) ? $dari->copy() : $mulai;
            $akhir = $akhir->gt($sampai) ? $sampai->copy() : $akhir;

            return $mulai->locale('id')->translatedFormat('d M').'-'.$akhir->locale('id')->translatedFormat('d M');
        }

        return match ($skala) {
            'bulan' => $tanggal->copy()->locale('id')->translatedFormat('M Y'),
            'semester' => sprintf(
                'Semester %d - %s-%s %d',
                $tanggal->month >= 7 ? 1 : 2,
                $tanggal->copy()->locale('id')->translatedFormat('M'),
                $tanggal->copy()->addMonthsNoOverflow(5)->locale('id')->translatedFormat('M'),
                $tanggal->year
            ),
            'tahun' => $tanggal->format('Y'),
            default => $tanggal->format('Y'),
        };
    }

    /** @return Collection<int, Siswa> */
    public function daftarSiswa(?array $kelasIds, string $search): Collection
    {
        if (mb_strlen($search) < 2) {
            return collect();
        }

        $pattern = '%'.$search.'%';
        $query = Siswa::withTrashed()
            ->with('kelas')
            ->where(fn ($q) => $q->where('nis', 'like', $pattern)->orWhere('nama', 'like', $pattern))
            ->orderByRaw('CASE WHEN nis = ? THEN 0 WHEN nama LIKE ? THEN 1 ELSE 2 END', [$search, $search.'%'])
            ->orderBy('nama')
            ->limit(100);
        if ($kelasIds !== null) {
            $query->whereIn('kelas_id', $kelasIds);
        }

        return $query->get();
    }

    /** @return Collection<int, Guru> */
    public function daftarGuru(User $user, string $search): Collection
    {
        if ($user->isGuru()) {
            return collect([$user->guruOrFail()->load('user')]);
        }

        if (mb_strlen($search) < 2) {
            return collect();
        }

        $pattern = '%'.$search.'%';

        return Guru::withTrashed()
            ->with('user')
            ->where(function ($query) use ($pattern) {
                $query->where('nuptk', 'like', $pattern)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('nama', 'like', $pattern));
            })
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->filter(fn (Guru $guru) => $guru->user !== null)
            ->sortBy(fn (Guru $guru) => $guru->user->nama, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }
}
