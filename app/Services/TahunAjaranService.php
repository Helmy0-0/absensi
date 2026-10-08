<?php

namespace App\Services;

use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class TahunAjaranService
{
    /** R-50: hanya satu tahun ajaran aktif, ditegakkan dalam transaksi. */
    public function simpan(array $data, ?TahunAjaran $ta = null): TahunAjaran
    {
        return DB::transaction(function () use ($data, $ta) {
            $aktif = (bool) ($data['is_aktif'] ?? false);

            if ($aktif) {
                TahunAjaran::query()->update(['is_aktif' => false]);
            }

            $ta ??= new TahunAjaran();
            $ta->fill([
                'nama' => $data['nama'],
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'],
                'is_aktif' => $aktif,
            ])->save();

            return $ta;
        });
    }

    public function tetapkanAktif(TahunAjaran $ta): void
    {
        DB::transaction(function () use ($ta) {
            TahunAjaran::query()->update(['is_aktif' => false]);
            $ta->update(['is_aktif' => true]);
        });
    }
}
