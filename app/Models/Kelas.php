<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelas extends Model
{
    use SoftDeletes;

    protected $table = 'kelas';

    protected $fillable = ['tahun_ajaran_id', 'nama', 'tingkat', 'ruangan'];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    public function siswaAktif(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id')->where('status', 'aktif');
    }

    public function relasi(): HasMany
    {
        return $this->hasMany(RelasiGuruKelas::class, 'kelas_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiKelas::class, 'kelas_id');
    }

    public function scopeTahunAktif(Builder $q): Builder
    {
        return $q->whereHas('tahunAjaran', fn ($t) => $t->where('is_aktif', true));
    }
}
