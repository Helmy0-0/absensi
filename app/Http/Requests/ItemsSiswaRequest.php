<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Basis untuk form yang memuat daftar status siswa: siswa[ID][status|keterangan]. */
abstract class ItemsSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function aturanItems(): array
    {
        return [
            'siswa' => ['nullable', 'array'],
            'siswa.*.status' => ['required', Rule::in(['hadir', 'sakit', 'izin', 'alpa'])],
            'siswa.*.keterangan' => ['required_if:siswa.*.status,sakit,izin', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'siswa.*.keterangan.required_if' => 'Keterangan wajib diisi untuk siswa yang sakit atau izin.',
            'siswa.*.status.required' => 'Status siswa wajib dipilih.',
            'siswa.*.status.in' => 'Status siswa tidak valid.',
        ];
    }
}
