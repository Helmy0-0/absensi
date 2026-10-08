<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiGuru extends Model
{
    protected $table = 'absensi_guru';

    protected $fillable = [
        'guru_id', 'tanggal', 'status', 'alasan', 'bukti_path', 'waktu_checkin', 'dibuat_sistem',
        'status_validasi', 'divalidasi_oleh', 'divalidasi_pada', 'catatan_validasi',
        'dikoreksi_oleh', 'dikoreksi_pada', 'catatan_koreksi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'waktu_checkin' => 'datetime',
            'divalidasi_pada' => 'datetime',
            'dikoreksi_pada' => 'datetime',
            'dibuat_sistem' => 'boolean',
        ];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id')->withTrashed();
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'divalidasi_oleh')->withTrashed();
    }

    public function korektor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh')->withTrashed();
    }
}
