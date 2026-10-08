<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Admin: semua kolom. Kesiswaan & Guru: hanya email dan no. HP. */
    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'no_hp' => ['nullable', 'string', 'max:20'],
        ];

        if ($user->isAdmin()) {
            $rules['nama'] = ['required', 'string', 'max:100'];
            $rules['username'] = ['required', 'string', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($user->id)];
            $rules['jabatan'] = ['nullable', 'string', 'max:100'];
        }

        return $rules;
    }
}
