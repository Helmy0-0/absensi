<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    /** Guru boleh mengabsen kelas yang punya relasi aktif dengannya (wali maupun mapel) — R-21. */
    public function absen(User $user, Kelas $kelas): bool
    {
        return $user->isGuru()
            && $user->guru
            && $user->guru->mengampu($kelas->id);
    }
}
