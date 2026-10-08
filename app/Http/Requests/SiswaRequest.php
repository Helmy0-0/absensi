<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware role:admin,kesiswaan
    }

    public function rules(): array
    {
        $siswa = $this->route('siswa');

        return [
            'nis' => ['required', 'string', 'max:30', Rule::unique('siswa', 'nis')->ignore($siswa?->id)],
            'nama' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
            'status' => ['required', Rule::in(['aktif', 'lulus', 'pindah', 'nonaktif'])],
        ];
    }
}
