<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $user = $this->route('user'); // null saat create
        $role = $user?->role ?? $this->input('role');
        $guruId = $user?->guru?->id;

        $rules = [
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'password' => $user
                ? ['nullable', 'string', Password::min(8)]
                : ['required', 'string', Password::min(8)],
        ];

        if (! $user) {
            // Username & role hanya diisi saat membuat akun (terkunci setelahnya).
            $rules['username'] = ['required', 'string', 'alpha_dash', 'max:50', Rule::unique('users', 'username')];
            $rules['role'] = ['required', Rule::in(['admin', 'kesiswaan', 'guru'])];
        }

        if ($role === 'guru') {
            $rules['nuptk'] = ['nullable', 'string', 'max:30', Rule::unique('guru', 'nuptk')->ignore($guruId)];
            $rules['status_guru'] = ['required', Rule::in(['aktif', 'cuti', 'nonaktif'])];
        }

        return $rules;
    }
}
