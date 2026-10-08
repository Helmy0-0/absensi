<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guru extends Model
{
    use SoftDeletes;

    protected $table = 'guru';

    protected $fillable = ['user_id', 'nuptk', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function relasi(): HasMany
    {
        return $this->hasMany(RelasiGuruKelas::class, 'guru_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiGuru::class, 'guru_id');
    }

    /** Guru aktif = status aktif + akun aktif (R-14). */
    public function scopeAktif(Builder $q): Builder
    {
        return $q->where('guru.status', 'aktif')
            ->whereNull('guru.deleted_at')
            ->whereHas('user', fn ($u) => $u->where('is_active', true));
    }

    /** Kelas yang diampu (wali maupun mapel), default hanya tahun ajaran aktif. */
    public function kelasDiampu(bool $hanyaTahunAktif = true): Builder
    {
        $q = Kelas::query()->whereHas('relasi', fn ($r) => $r->where('guru_id', $this->id));
        if ($hanyaTahunAktif) {
            $q->tahunAktif();
        }

        return $q;
    }

    public function mengampu(int $kelasId): bool
    {
        return $this->relasi()->where('kelas_id', $kelasId)->exists();
    }
}
