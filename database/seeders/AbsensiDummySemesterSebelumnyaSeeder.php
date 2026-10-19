<?php

namespace Database\Seeders;

use App\Models\AbsensiGuru;
use App\Models\AbsensiKelas;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\RelasiGuruKelas;
use App\Models\User;
use App\Support\Kalender;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AbsensiDummySemesterSebelumnyaSeeder extends Seeder
{
    public function run(): void
    {
        $guruList = Guru::aktif()->with('user')->get();
        $kelasList = Kelas::tahunAktif()
            ->with('siswaAktif:id,kelas_id')
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->get();
        $validator = User::where('is_active', true)->where('role', 'kesiswaan')->first()
            ?? User::where('is_active', true)->where('role', 'admin')->first();

        if ($guruList->isEmpty() || $kelasList->isEmpty() || ! $validator) {
            throw new RuntimeException('Seeder membutuhkan guru aktif, kelas aktif, dan akun admin/kesiswaan aktif.');
        }

        // Isi enam bulan penuh yang langsung mendahului bulan dummy terbaru.
        $akhir = Kalender::hariIni()->subMonthNoOverflow()->startOfMonth()->subDay();
        $mulai = $akhir->copy()->startOfMonth()->subMonthsNoOverflow(5);
        $guruById = $guruList->keyBy('id');
        $waliPerKelas = RelasiGuruKelas::query()
            ->whereIn('kelas_id', $kelasList->pluck('id'))
            ->where('peran', 'wali')
            ->get()
            ->keyBy('kelas_id');
        $jumlahAbsenGuru = 0;
        $jumlahLaporanKelas = 0;
        $jumlahDetailSiswa = 0;

        for ($tanggal = $mulai->copy(); $tanggal->lte($akhir); $tanggal->addDay()) {
            if (Kalender::libur($tanggal)) {
                continue;
            }

            DB::transaction(function () use (
                $tanggal,
                $guruList,
                $kelasList,
                $guruById,
                $waliPerKelas,
                $validator,
                &$jumlahAbsenGuru,
                &$jumlahLaporanKelas,
                &$jumlahDetailSiswa
            ): void {
                $tanggalSql = $tanggal->toDateString();
                $waktu = $tanggal->copy()->setTime(7, 0);
                $divalidasiPada = $waktu->copy()->addHours(2)->toDateTimeString();
                $dibuatPada = $waktu->toDateTimeString();

                $absenGuru = $guruList->map(function (Guru $guru) use ($tanggalSql, $waktu, $divalidasiPada, $validator): array {
                    $acak = crc32("guru:{$guru->id}:{$tanggalSql}") % 100;
                    $status = match (true) {
                        $acak < 88 => 'hadir',
                        $acak < 93 => 'sakit',
                        $acak < 97 => 'izin',
                        default => 'alpa',
                    };
                    $stamp = $waktu->copy()->addMinutes(crc32("jam:{$guru->id}:{$tanggalSql}") % 46);

                    return [
                        'guru_id' => $guru->id,
                        'tanggal' => $tanggalSql,
                        'status' => $status,
                        'alasan' => match ($status) {
                            'sakit' => 'Data simulasi: sakit.',
                            'izin' => 'Data simulasi: keperluan keluarga.',
                            default => null,
                        },
                        'waktu_checkin' => $status === 'alpa' ? null : $stamp->toDateTimeString(),
                        'dibuat_sistem' => $status === 'alpa',
                        'status_validasi' => 'tervalidasi',
                        'divalidasi_oleh' => $validator->id,
                        'divalidasi_pada' => $divalidasiPada,
                        'created_at' => $stamp->toDateTimeString(),
                        'updated_at' => $divalidasiPada,
                    ];
                })->all();

                $jumlahAbsenGuru += AbsensiGuru::query()->insertOrIgnore($absenGuru);
                $laporanLama = AbsensiKelas::query()
                    ->where('tanggal', $tanggalSql)
                    ->whereIn('kelas_id', $kelasList->pluck('id'))
                    ->pluck('id', 'kelas_id');

                foreach ($kelasList as $index => $kelas) {
                    if ($laporanLama->has($kelas->id)) {
                        continue;
                    }

                    $waliId = $waliPerKelas->get($kelas->id)?->guru_id;
                    $pelapor = $guruById->get($waliId) ?? $guruList[$index % $guruList->count()];
                    $laporan = AbsensiKelas::create([
                        'kelas_id' => $kelas->id,
                        'tanggal' => $tanggalSql,
                        'dilaporkan_oleh' => $pelapor->user_id,
                        'waktu_laporan' => $waktu->copy()->addMinutes(30),
                        'diinput_manual' => false,
                        'status_validasi' => 'tervalidasi',
                        'divalidasi_oleh' => $validator->id,
                        'divalidasi_pada' => $divalidasiPada,
                    ]);

                    $detail = $kelas->siswaAktif->map(function ($siswa) use ($laporan, $tanggalSql, $dibuatPada): array {
                        $acak = crc32("siswa:{$siswa->id}:{$tanggalSql}") % 100;
                        $status = match (true) {
                            $acak < 92 => 'hadir',
                            $acak < 96 => 'sakit',
                            $acak < 99 => 'izin',
                            default => 'alpa',
                        };

                        return [
                            'absensi_kelas_id' => $laporan->id,
                            'siswa_id' => $siswa->id,
                            'status' => $status,
                            'keterangan' => match ($status) {
                                'sakit' => 'Data simulasi: sakit.',
                                'izin' => 'Data simulasi: izin.',
                                'alpa' => 'Data simulasi.',
                                default => null,
                            },
                            'created_at' => $dibuatPada,
                            'updated_at' => $dibuatPada,
                        ];
                    })->all();

                    foreach (array_chunk($detail, 500) as $potongan) {
                        $jumlahDetailSiswa += DB::table('absensi_siswa')->insertOrIgnore($potongan);
                    }

                    $jumlahLaporanKelas++;
                }
            });
        }

        $this->command?->info("Data dummy {$mulai->format('F Y')}–{$akhir->format('F Y')}: {$jumlahAbsenGuru} absen guru, {$jumlahLaporanKelas} laporan kelas, {$jumlahDetailSiswa} detail siswa baru. Semua status baru tervalidasi.");
    }
}
