<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AbsenMandiriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role + Policy dicek di controller
    }

    public function rules(): array
    {
        $absen = $this->route('absensiGuru'); // ada saat perbaikan
        $izinSakit = in_array($this->input('status'), ['izin', 'sakit'], true);

        return [
            'status' => ['required', Rule::in(['hadir', 'izin', 'sakit'])], // alpa tidak bisa dipilih guru (R-11)
            'alasan' => ['required_if:status,izin,sakit', 'nullable', 'string', 'max:1000'],
            'bukti' => [
                Rule::requiredIf($izinSakit && ! $absen?->bukti_path),
                'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:'.config('absensi.bukti_maks_kb', 2048),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required_if' => 'Alasan wajib diisi untuk izin/sakit.',
            'bukti.required' => 'Bukti (surat tugas/surat dokter) wajib diunggah untuk izin/sakit.',
        ];
    }
}
