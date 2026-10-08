<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KoreksiGuruRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guru_id' => ['required', 'exists:guru,id'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
            'alasan' => ['required_if:status,izin,sakit', 'nullable', 'string', 'max:1000'],
            'catatan_koreksi' => ['required', 'string', 'min:5', 'max:500'],
            'bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:'.config('absensi.bukti_maks_kb', 2048)],
        ];
    }

    public function messages(): array
    {
        return ['alasan.required_if' => 'Alasan wajib diisi untuk izin/sakit.'];
    }
}
