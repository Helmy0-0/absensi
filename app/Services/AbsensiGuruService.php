<?php

namespace App\Services;

use App\Exceptions\BisnisException;
use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\User;
use App\Support\Kalender;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AbsensiGuruService
{
    /** R-10..R-13: absen mandiri hari ini. */
    public function kirimHariIni(Guru $guru, array $data, ?UploadedFile $bukti): AbsensiGuru
    {
        $hariIni = Kalender::hariIni();

        if (Kalender::libur($hariIni)) {
            throw new BisnisException('Hari ini bukan hari efektif (libur).');
        }

        try {
            return DB::transaction(function () use ($guru, $data, $bukti, $hariIni) {
                $sudahAda = AbsensiGuru::where('guru_id', $guru->id)
                    ->where('tanggal', $hariIni->toDateString())
                    ->lockForUpdate()
                    ->exists();

                if ($sudahAda) {
                    throw new BisnisException('Anda sudah melakukan absen hari ini.');
                }

                $absen = new AbsensiGuru([
                    'guru_id' => $guru->id,
                    'tanggal' => $hariIni->toDateString(),
                    'status_validasi' => 'pending',
                    'waktu_checkin' => Kalender::sekarang(),
                    'dibuat_sistem' => false,
                ]);
                $this->isiDariGuru($absen, $data, $bukti);
                $absen->save();

                return $absen;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw new BisnisException('Anda sudah melakukan absen hari ini.');
        }
    }

    /** R-32: guru memperbaiki absen yang ditolak (tanggal lampau diperbolehkan). */
    public function perbaiki(AbsensiGuru $absen, array $data, ?UploadedFile $bukti): AbsensiGuru
    {
        return DB::transaction(function () use ($absen, $data, $bukti) {
            $absen = AbsensiGuru::lockForUpdate()->findOrFail($absen->id);

            if ($absen->status_validasi !== 'ditolak') {
                throw new BisnisException('Hanya absen yang ditolak yang dapat diperbaiki.');
            }

            $this->isiDariGuru($absen, $data, $bukti);
            $absen->fill([
                'status_validasi' => 'pending',
                'waktu_checkin' => Kalender::sekarang(),
                'catatan_validasi' => null,
                'divalidasi_oleh' => null,
                'divalidasi_pada' => null,
            ])->save();

            return $absen;
        });
    }

    public function setujui(AbsensiGuru $absen, User $oleh): void
    {
        $diubah = AbsensiGuru::where('id', $absen->id)
            ->where('status_validasi', 'pending')
            ->update([
                'status_validasi' => 'tervalidasi',
                'divalidasi_oleh' => $oleh->id,
                'divalidasi_pada' => Kalender::sekarang(),
                'catatan_validasi' => null,
                'updated_at' => now(),
            ]);

        if (! $diubah) {
            throw new BisnisException('Hanya data berstatus menunggu yang dapat disetujui.');
        }
    }

    public function tolak(AbsensiGuru $absen, User $oleh, string $catatan): void
    {
        $diubah = AbsensiGuru::where('id', $absen->id)
            ->where('status_validasi', 'pending')
            ->update([
                'status_validasi' => 'ditolak',
                'divalidasi_oleh' => $oleh->id,
                'divalidasi_pada' => Kalender::sekarang(),
                'catatan_validasi' => $catatan,
                'updated_at' => now(),
            ]);

        if (! $diubah) {
            throw new BisnisException('Hanya data berstatus menunggu yang dapat ditolak.');
        }
    }

    /** R-34: hanya memproses `pending`. */
    public function setujuiSemua(Carbon $tanggal, User $oleh): int
    {
        return AbsensiGuru::where('tanggal', $tanggal->toDateString())
            ->where('status_validasi', 'pending')
            ->update([
                'status_validasi' => 'tervalidasi',
                'divalidasi_oleh' => $oleh->id,
                'divalidasi_pada' => Kalender::sekarang(),
                'updated_at' => now(),
            ]);
    }

    /** R-35: koreksi (juga untuk membuat catatan bagi guru yang "Belum absen"). */
    public function koreksi(Guru $guru, Carbon $tanggal, User $oleh, array $data, ?UploadedFile $bukti): AbsensiGuru
    {
        if (Kalender::libur($tanggal)) {
            throw new BisnisException('Tanggal tersebut adalah hari libur.');
        }
        if ($tanggal->gt(Kalender::hariIni())) {
            throw new BisnisException('Tidak dapat mengoreksi tanggal yang belum terjadi.');
        }

        $status = $data['status'];
        if (! in_array($status, ['hadir', 'izin', 'sakit', 'alpa'], true)) {
            throw new BisnisException('Status tidak valid.');
        }
        if (in_array($status, ['izin', 'sakit'], true) && blank($data['alasan'] ?? null)) {
            throw new BisnisException('Alasan wajib diisi untuk izin/sakit.');
        }

        return DB::transaction(function () use ($guru, $tanggal, $oleh, $data, $bukti, $status) {
            $absen = AbsensiGuru::where('guru_id', $guru->id)
                ->where('tanggal', $tanggal->toDateString())
                ->lockForUpdate()
                ->first()
                ?? new AbsensiGuru(['guru_id' => $guru->id, 'tanggal' => $tanggal->toDateString()]);

            if ($status === 'hadir') {
                $this->hapusBukti($absen);
                $absen->bukti_path = null;
                $absen->alasan = null;
            } else {
                $absen->alasan = $data['alasan'] ?? $absen->alasan;
            }

            if ($bukti && $status !== 'hadir') {
                $this->hapusBukti($absen);
                $absen->bukti_path = $bukti->store('bukti', 'local');
            }

            $sekarang = Kalender::sekarang();
            $absen->fill([
                'status' => $status,
                'status_validasi' => 'tervalidasi',
                'divalidasi_oleh' => $oleh->id,
                'divalidasi_pada' => $sekarang,
                'catatan_validasi' => null,
                'dikoreksi_oleh' => $oleh->id,
                'dikoreksi_pada' => $sekarang,
                'catatan_koreksi' => $data['catatan_koreksi'],
            ])->save();

            return $absen;
        });
    }

    /** R-11, R-12 */
    private function isiDariGuru(AbsensiGuru $absen, array $data, ?UploadedFile $bukti): void
    {
        $status = $data['status'] ?? null;

        if (! in_array($status, ['hadir', 'izin', 'sakit'], true)) {
            throw new BisnisException('Status yang dipilih tidak valid. Guru hanya dapat memilih hadir, izin, atau sakit.');
        }

        if ($status === 'hadir') {
            $this->hapusBukti($absen);
            $absen->alasan = null;
            $absen->bukti_path = null;
        } else {
            if (blank($data['alasan'] ?? null)) {
                throw new BisnisException('Alasan wajib diisi untuk izin/sakit.');
            }
            $absen->alasan = $data['alasan'];

            if ($bukti) {
                $this->hapusBukti($absen);
                $absen->bukti_path = $bukti->store('bukti', 'local');
            } elseif (! $absen->bukti_path) {
                throw new BisnisException('Bukti (surat tugas/surat dokter) wajib diunggah untuk izin/sakit.');
            }
        }

        $absen->status = $status;
    }

    private function hapusBukti(AbsensiGuru $absen): void
    {
        if ($absen->bukti_path) {
            Storage::disk('local')->delete($absen->bukti_path);
        }
    }
}
