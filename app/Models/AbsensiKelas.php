<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsensiKelas extends Model
{
    protected $table = 'absensi_kelas';

    protected $fillable = [
        'kelas_id', 'tanggal', 'dilaporkan_oleh', 'waktu_laporan', 'diinput_manual', 'status_validasi',
        'divalidasi_oleh', 'divalidasi_pada', 'catatan_validasi',
        'dikoreksi_oleh', 'dikoreksi_pada', 'catatan_koreksi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'waktu_laporan' => 'datetime',
            'divalidasi_pada' => 'datetime',
            'dikoreksi_pada' => 'datetime',
            'diinput_manual' => 'boolean',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id')->withTrashed();
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilaporkan_oleh')->withTrashed();
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'divalidasi_oleh')->withTrashed();
    }

    public function korektor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh')->withTrashed();
    }

    public function detail(): HasMany
    {
        return $this->hasMany(AbsensiSiswa::class, 'absensi_kelas_id');
    }
}
