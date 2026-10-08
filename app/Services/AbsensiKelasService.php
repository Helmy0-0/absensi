<?php

namespace App\Services;

use App\Exceptions\BisnisException;
use App\Models\AbsensiKelas;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\User;
use App\Support\Kalender;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AbsensiKelasService
{
    /**
     * Titik masuk dari guru. Otorisasi (guru mengampu kelas) dicek di controller lewat Policy.
     * - Sudah ada laporan `ditolak`  → perbaikan (R-32, boleh tanggal lampau)
     * - Belum ada laporan            → kirim baru (hanya hari ini, R-04)
     */
    public function simpanOlehGuru(Guru $guru, Kelas $kelas, Carbon $tanggal, array $items): AbsensiKelas
    {
        $laporan = AbsensiKelas::with('pelapor')
            ->where('kelas_id', $kelas->id)
            ->where('tanggal', $tanggal->toDateString())
            ->first();

        if ($laporan && $laporan->status_validasi === 'ditolak') {
            return $this->perbaiki($guru, $laporan, $items);
        }

        if ($laporan) {
            $nama = $laporan->pelapor?->nama;
            throw new BisnisException($nama
                ? "Kelas ini sudah diabsen oleh {$nama}."
                : 'Kelas ini sudah memiliki laporan untuk tanggal tersebut.');
        }

        if (! $tanggal->isSameDay(Kalender::hariIni())) {
            throw new BisnisException('Absensi hanya dapat diinput untuk hari ini.');
        }

        return $this->kirim($guru, $kelas, $tanggal, $items);
    }

    /** R-20..R-24, R-22 (konkurensi lewat transaksi + unique constraint). */
    private function kirim(Guru $guru, Kelas $kelas, Carbon $tanggal, array $items): AbsensiKelas
    {
        if (Kalender::libur($tanggal)) {
            throw new BisnisException('Hari ini bukan hari efektif (libur).');
        }

        $siswaIds = $kelas->siswaAktif()->pluck('id');
        if ($siswaIds->isEmpty()) {
            throw new BisnisException('Kelas ini belum memiliki siswa aktif.');
        }

        try {
            return DB::transaction(function () use ($guru, $kelas, $tanggal, $items, $siswaIds) {
                $laporan = AbsensiKelas::create([
                    'kelas_id' => $kelas->id,
                    'tanggal' => $tanggal->toDateString(),
                    'dilaporkan_oleh' => $guru->user_id,
                    'waktu_laporan' => Kalender::sekarang(),
                    'diinput_manual' => false,
                    'status_validasi' => 'pending',
                ]);

                $this->isiDetail($laporan, $siswaIds, $items);

                return $laporan;
            });
        } catch (UniqueConstraintViolationException $e) {
            $l = AbsensiKelas::with('pelapor')
                ->where('kelas_id', $kelas->id)
                ->where('tanggal', $tanggal->toDateString())
                ->first();

            throw new BisnisException('Kelas ini baru saja diabsen oleh '.($l?->pelapor?->nama ?? 'guru lain').'.');
        }
    }

    /** R-32: guru mana pun yang terdaftar di kelas boleh memperbaiki laporan ditolak. */
    private function perbaiki(Guru $guru, AbsensiKelas $laporan, array $items): AbsensiKelas
    {
        return DB::transaction(function () use ($guru, $laporan, $items) {
            $laporan = AbsensiKelas::lockForUpdate()->findOrFail($laporan->id);

            if ($laporan->status_validasi !== 'ditolak') {
                throw new BisnisException('Laporan ini sudah diperbaiki atau tidak lagi berstatus ditolak.');
            }

            $siswaIds = $laporan->detail()->pluck('siswa_id');
            $this->isiDetail($laporan, $siswaIds, $items);

            $laporan->fill([
                'dilaporkan_oleh' => $guru->user_id,
                'waktu_laporan' => Kalender::sekarang(),
                'status_validasi' => 'pending',
                'catatan_validasi' => null,
                'divalidasi_oleh' => null,
                'divalidasi_pada' => null,
            ])->save();

            return $laporan;
        });
    }

    public function setujui(AbsensiKelas $laporan, User $oleh): void
    {
        $diubah = AbsensiKelas::where('id', $laporan->id)
            ->where('status_validasi', 'pending')
            ->update([
                'status_validasi' => 'tervalidasi',
                'divalidasi_oleh' => $oleh->id,
                'divalidasi_pada' => Kalender::sekarang(),
                'catatan_validasi' => null,
                'updated_at' => now(),
            ]);

        if (! $diubah) {
            throw new BisnisException('Hanya laporan berstatus menunggu yang dapat divalidasi.');
        }
    }

    public function tolak(AbsensiKelas $laporan, User $oleh, string $catatan): void
    {
        $diubah = AbsensiKelas::where('id', $laporan->id)
            ->where('status_validasi', 'pending')
            ->update([
                'status_validasi' => 'ditolak',
                'divalidasi_oleh' => $oleh->id,
                'divalidasi_pada' => Kalender::sekarang(),
                'catatan_validasi' => $catatan,
                'updated_at' => now(),
            ]);

        if (! $diubah) {
            throw new BisnisException('Hanya laporan berstatus menunggu yang dapat ditolak.');
        }
    }

    /** R-34: tidak menyentuh `ditolak` maupun `tidak_diabsen`. */
    public function setujuiSemua(Carbon $tanggal, User $oleh): int
    {
        return AbsensiKelas::where('tanggal', $tanggal->toDateString())
            ->where('status_validasi', 'pending')
            ->update([
                'status_validasi' => 'tervalidasi',
                'divalidasi_oleh' => $oleh->id,
                'divalidasi_pada' => Kalender::sekarang(),
                'updated_at' => now(),
            ]);
    }

    /** R-35: koreksi daftar kehadiran siswa pada laporan yang sudah ada. */
    public function koreksi(AbsensiKelas $laporan, User $oleh, array $items, string $catatan): AbsensiKelas
    {
        if ($laporan->status_validasi === 'tidak_diabsen') {
            throw new BisnisException('Laporan ini belum diisi. Gunakan Input Manual.');
        }

        return DB::transaction(function () use ($laporan, $oleh, $items, $catatan) {
            $laporan = AbsensiKelas::lockForUpdate()->findOrFail($laporan->id);
            $this->isiDetail($laporan, $laporan->detail()->pluck('siswa_id'), $items);

            $sekarang = Kalender::sekarang();
            $laporan->fill([
                'status_validasi' => 'tervalidasi',
                'divalidasi_oleh' => $oleh->id,
                'divalidasi_pada' => $sekarang,
                'catatan_validasi' => null,
                'dikoreksi_oleh' => $oleh->id,
                'dikoreksi_pada' => $sekarang,
                'catatan_koreksi' => $catatan,
            ])->save();

            return $laporan;
        });
    }

    /** R-26: isi laporan kelas `tidak_diabsen` (atau yang belum ada) untuk tanggal mana pun. */
    public function inputManual(Kelas $kelas, Carbon $tanggal, User $oleh, array $items): AbsensiKelas
    {
        if (Kalender::libur($tanggal)) {
            throw new BisnisException('Tanggal tersebut adalah hari libur.');
        }
        if ($tanggal->gt(Kalender::hariIni())) {
            throw new BisnisException('Tidak dapat menginput tanggal yang belum terjadi.');
        }

        $siswaIds = $kelas->siswaAktif()->pluck('id');
        if ($siswaIds->isEmpty()) {
            throw new BisnisException('Kelas ini belum memiliki siswa aktif.');
        }

        try {
            return DB::transaction(function () use ($kelas, $tanggal, $oleh, $items, $siswaIds) {
                $laporan = AbsensiKelas::where('kelas_id', $kelas->id)
                    ->where('tanggal', $tanggal->toDateString())
                    ->lockForUpdate()
                    ->first();

                if ($laporan && $laporan->status_validasi !== 'tidak_diabsen') {
                    throw new BisnisException('Laporan untuk kelas dan tanggal ini sudah ada. Gunakan Koreksi.');
                }

                $laporan ??= new AbsensiKelas(['kelas_id' => $kelas->id, 'tanggal' => $tanggal->toDateString()]);

                $sekarang = Kalender::sekarang();
                $laporan->fill([
                    'dilaporkan_oleh' => $oleh->id,
                    'waktu_laporan' => $sekarang,
                    'diinput_manual' => true,
                    'status_validasi' => 'tervalidasi',
                    'divalidasi_oleh' => $oleh->id,
                    'divalidasi_pada' => $sekarang,
                ])->save();

                $this->isiDetail($laporan, $siswaIds, $items);

                return $laporan;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw new BisnisException('Laporan untuk kelas dan tanggal ini baru saja dibuat. Muat ulang halaman.');
        }
    }

    /**
     * Mengganti seluruh detail siswa. Siswa yang tidak ada di $items dianggap hadir (R-23).
     *
     * @param  array<int|string, array{status?: string, keterangan?: ?string}>  $items
     */
    private function isiDetail(AbsensiKelas $laporan, Collection $siswaIds, array $items): void
    {
        $sekarang = Kalender::sekarang()->toDateTimeString();
        $baris = [];

        foreach ($siswaIds as $id) {
            $status = $items[$id]['status'] ?? 'hadir';
            $keterangan = trim((string) ($items[$id]['keterangan'] ?? ''));

            if (! in_array($status, ['hadir', 'izin', 'sakit', 'alpa'], true)) {
                throw new BisnisException('Status siswa tidak valid.');
            }
            if (in_array($status, ['sakit', 'izin'], true) && $keterangan === '') {
                throw new BisnisException('Keterangan wajib diisi untuk siswa yang sakit atau izin.');
            }

            $baris[] = [
                'absensi_kelas_id' => $laporan->id,
                'siswa_id' => $id,
                'status' => $status,
                'keterangan' => $status === 'hadir' ? null : ($keterangan !== '' ? $keterangan : null),
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ];
        }

        AbsensiSiswa::where('absensi_kelas_id', $laporan->id)->delete();
        AbsensiSiswa::insert($baris);
    }
}
