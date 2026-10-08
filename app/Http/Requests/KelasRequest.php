<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $kelas = $this->route('kelas');

        return [
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'nama' => [
                'required', 'string', 'max:50',
                Rule::unique('kelas', 'nama')
                    ->where('tahun_ajaran_id', $this->input('tahun_ajaran_id'))
                    ->ignore($kelas?->id),
            ],
            'tingkat' => ['required', Rule::in([10, 11, 12])],
            'ruangan' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return ['nama.unique' => 'Nama kelas sudah dipakai pada tahun ajaran tersebut.'];
    }
}
