<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guru_id' => ['required', 'exists:guru,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'peran' => ['required', Rule::in(['wali', 'guru_mapel'])],
            'mata_pelajaran' => ['required_if:peran,guru_mapel', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return ['mata_pelajaran.required_if' => 'Mata pelajaran wajib diisi untuk guru mapel.'];
    }
}
