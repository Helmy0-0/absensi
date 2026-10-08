<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RelasiGuruKelas extends Model
{
    use SoftDeletes;

    protected $table = 'relasi_guru_kelas';

    protected $fillable = ['guru_id', 'kelas_id', 'peran', 'mata_pelajaran', 'wali_key'];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id')->withTrashed();
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id')->withTrashed();
    }
}
