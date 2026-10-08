<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class Kalender
{
    public static function tz(): string
    {
        return config('absensi.timezone', 'Asia/Jakarta');
    }

    public static function sekarang(): Carbon
    {
        return Carbon::now(self::tz());
    }

    public static function hariIni(): Carbon
    {
        return Carbon::today(self::tz());
    }

    public static function parse(?string $tanggal): Carbon
    {
        if (! $tanggal) {
            return self::hariIni();
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $tanggal, self::tz())->startOfDay();
        } catch (\Throwable $e) {
            return self::hariIni();
        }
    }

    public static function parseBulan(?string $nilai, Carbon $default): Carbon
    {
        if (! $nilai) {
            return $default->copy();
        }
        try {
            return Carbon::createFromFormat('!Y-m', $nilai, self::tz());
        } catch (\Throwable $e) {
            return $default->copy();
        }
    }

    public static function libur(CarbonInterface $tanggal): bool
    {
        return in_array($tanggal->dayOfWeekIso, config('absensi.libur_mingguan', [6, 7]), true);
    }

    /** Angka WEEKDAY() MySQL (Senin=0) untuk hari libur mingguan. */
    public static function weekdayLibur(): array
    {
        return array_map(fn ($d) => (int) $d - 1, config('absensi.libur_mingguan', [6, 7]));
    }

    public static function sqlBukanLibur(string $kolom): string
    {
        $daftar = implode(',', self::weekdayLibur());

        return "WEEKDAY($kolom) NOT IN ($daftar)";
    }

    /** N hari efektif terakhir (termasuk hari ini bila efektif), urut lama → baru. */
    public static function hariEfektifTerakhir(int $n = 7): array
    {
        $hasil = [];
        $t = self::hariIni();
        for ($i = 0; $i < 60 && count($hasil) < $n; $i++) {
            if (! self::libur($t)) {
                array_unshift($hasil, $t->copy());
            }
            $t->subDay();
        }

        return $hasil;
    }

    public static function tanggalPanjang(CarbonInterface $t): string
    {
        return $t->copy()->locale('id')->translatedFormat('l, d F Y');
    }

    public static function bulanTahun(CarbonInterface $t): string
    {
        return $t->copy()->locale('id')->translatedFormat('F Y');
    }
}
