<?php

namespace App\Services;

use App\Exceptions\BisnisException;
use App\Models\RelasiGuruKelas;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class RelasiService
{
    public function simpan(array $data, ?RelasiGuruKelas $relasi = null): RelasiGuruKelas
    {
        $peran = $data['peran'];
        $kelasId = (int) $data['kelas_id'];
        $mapel = trim((string) ($data['mata_pelajaran'] ?? '')) ?: null;

        if ($peran === 'guru_mapel' && ! $mapel) {
            throw new BisnisException('Mata pelajaran wajib diisi untuk guru mapel.');
        }

        // R-61: satu wali per kelas (validasi aplikasi; unique index sebagai pengaman).
        if ($peran === 'wali') {
            $adaWali = RelasiGuruKelas::where('kelas_id', $kelasId)
                ->where('peran', 'wali')
                ->when($relasi, fn ($q) => $q->where('id', '!=', $relasi->id))
                ->exists();

            if ($adaWali) {
                throw new BisnisException('Kelas ini sudah memiliki wali kelas.');
            }
        }

        $duplikat = RelasiGuruKelas::where('guru_id', $data['guru_id'])
            ->where('kelas_id', $kelasId)
            ->where('peran', $peran)
            ->where('mata_pelajaran', $mapel)
            ->when($relasi, fn ($q) => $q->where('id', '!=', $relasi->id))
            ->exists();

        if ($duplikat) {
            throw new BisnisException('Relasi yang sama sudah ada.');
        }

        try {
            return DB::transaction(function () use ($data, $relasi, $peran, $kelasId, $mapel) {
                $relasi ??= new RelasiGuruKelas();
                $relasi->fill([
                    'guru_id' => $data['guru_id'],
                    'kelas_id' => $kelasId,
                    'peran' => $peran,
                    'mata_pelajaran' => $mapel,
                    'wali_key' => $peran === 'wali' ? $kelasId : null,
                ])->save();

                return $relasi;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw new BisnisException('Kelas ini sudah memiliki wali kelas.');
        }
    }

    /** R-63: kosongkan wali_key sebelum soft delete agar wali baru bisa ditetapkan. */
    public function hapus(RelasiGuruKelas $relasi): void
    {
        DB::transaction(function () use ($relasi) {
            $relasi->update(['wali_key' => null]);
            $relasi->delete();
        });
    }
}
