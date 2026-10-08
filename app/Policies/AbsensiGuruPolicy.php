<?php

namespace App\Policies;

use App\Models\AbsensiGuru;
use App\Models\User;

class AbsensiGuruPolicy
{
    /** Guru hanya boleh memperbaiki absennya sendiri yang ditolak — R-32. */
    public function perbaiki(User $user, AbsensiGuru $absen): bool
    {
        return $user->isGuru()
            && $user->guru
            && $user->guru->id === $absen->guru_id
            && $absen->status_validasi === 'ditolak';
    }

    /** Bukti hanya dapat dibuka pemilik, kesiswaan, dan admin. */
    public function lihatBukti(User $user, AbsensiGuru $absen): bool
    {
        if ($user->isAdmin() || $user->isKesiswaan()) {
            return true;
        }

        return $user->isGuru() && $user->guru && $user->guru->id === $absen->guru_id;
    }
}
