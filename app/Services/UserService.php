<?php

namespace App\Services;

use App\Exceptions\BisnisException;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function buat(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $statusGuru = $data['status_guru'] ?? 'aktif';

            $user = User::create([
                'nama' => $data['nama'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'no_hp' => $data['no_hp'] ?? null,
                'role' => $data['role'],
                'jabatan' => $data['jabatan'] ?? null,
                'is_active' => $data['role'] === 'guru' && $statusGuru === 'nonaktif'
                    ? false
                    : (bool) ($data['is_active'] ?? true),
            ]);

            if ($user->role === 'guru') {
                Guru::create([
                    'user_id' => $user->id,
                    'nuptk' => ($data['nuptk'] ?? null) ?: null,
                    'status' => $statusGuru,
                ]);
            }

            return $user;
        });
    }

    /** Username & role terkunci; password opsional (Admin dapat mereset). */
    public function ubah(User $user, array $data, User $pelaku): User
    {
        return DB::transaction(function () use ($user, $data, $pelaku) {
            $isActive = (bool) ($data['is_active'] ?? false);
            $statusGuru = $data['status_guru'] ?? null;

            if ($user->role === 'guru' && $statusGuru === 'nonaktif') {
                $isActive = false; // guru.status nonaktif → akun nonaktif
            }

            if (! $isActive && $user->is_active) {
                $this->pastikanBolehDinonaktifkan($user, $pelaku);
            }

            $user->fill([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'no_hp' => $data['no_hp'] ?? null,
                'jabatan' => $data['jabatan'] ?? null,
                'is_active' => $isActive,
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();

            if ($user->role === 'guru') {
                Guru::withTrashed()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['nuptk' => ($data['nuptk'] ?? null) ?: null, 'status' => $statusGuru ?? 'aktif']
                );
            }

            return $user;
        });
    }

    /** R-03, R-71: soft delete. */
    public function hapus(User $user, User $pelaku): void
    {
        $this->pastikanBolehDinonaktifkan($user, $pelaku);

        DB::transaction(function () use ($user) {
            if ($user->guru) {
                $user->guru->update(['status' => 'nonaktif']);
                $user->guru->delete();
            }
            $user->update(['is_active' => false]);
            $user->delete();
        });
    }

    private function pastikanBolehDinonaktifkan(User $user, User $pelaku): void
    {
        if ($user->id === $pelaku->id) {
            throw new BisnisException('Anda tidak dapat menghapus atau menonaktifkan akun yang sedang Anda pakai.');
        }

        if ($user->role === 'admin' && $user->is_active) {
            $adminAktifLain = User::where('role', 'admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->exists();

            if (! $adminAktifLain) {
                throw new BisnisException('Tidak dapat menonaktifkan/menghapus admin aktif terakhir.');
            }
        }
    }
}
