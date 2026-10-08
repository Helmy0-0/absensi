<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiSiswa extends Model
{
    protected $table = 'absensi_siswa';

    protected $fillable = ['absensi_kelas_id', 'siswa_id', 'status', 'keterangan'];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(AbsensiKelas::class, 'absensi_kelas_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id')->withTrashed();
    }
}
