<?php

use Illuminate\Support\Facades\Schedule;

// R-14, R-25: alpa otomatis & penandaan kelas "tidak diabsen" (idempoten).
// Server produksi wajib punya cron: * * * * * php artisan schedule:run
Schedule::command('absensi:tutup-hari')
    ->dailyAt(config('absensi.jam_alpa_otomatis', '23:59'))
    ->timezone(config('absensi.timezone', 'Asia/Jakarta'))
    ->withoutOverlapping();
