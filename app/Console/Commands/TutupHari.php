<?php

namespace App\Console\Commands;

use App\Services\TutupHariService;
use App\Support\Kalender;
use Illuminate\Console\Command;

class TutupHari extends Command
{
    protected $signature = 'absensi:tutup-hari {--tanggal= : Format Y-m-d (default: hari ini, zona Asia/Jakarta)}';

    protected $description = 'Membuat alpa otomatis guru dan menandai kelas yang tidak diabsen (idempoten).';

    public function handle(TutupHariService $service): int
    {
        $tanggal = Kalender::parse($this->option('tanggal'));

        if (Kalender::libur($tanggal)) {
            $this->info($tanggal->toDateString().' adalah hari libur. Tidak ada yang dibuat.');

            return self::SUCCESS;
        }

        $hasil = $service->jalankan($tanggal);

        $this->info(sprintf(
            '%s: %d guru dialpakan, %d kelas ditandai tidak diabsen.',
            $tanggal->toDateString(),
            $hasil['guru_alpa'],
            $hasil['kelas_tidak_diabsen']
        ));

        return self::SUCCESS;
    }
}
