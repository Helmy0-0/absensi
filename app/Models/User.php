<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'nama', 'username', 'email', 'password', 'no_hp', 'role', 'jabatan', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class, 'user_id');
    }

    public function guruOrFail(): Guru
    {
        $guru = $this->guru;
        abort_unless($guru, 403, 'Akun ini belum terhubung ke data guru.');

        return $guru;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isKesiswaan(): bool
    {
        return $this->role === 'kesiswaan';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function labelRole(): string
    {
        return ['admin' => 'Admin', 'kesiswaan' => 'Kesiswaan', 'guru' => 'Guru'][$this->role] ?? $this->role;
    }
}
