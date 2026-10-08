<?php

namespace App\Http\Requests;

class InputManualRequest extends ItemsSiswaRequest
{
    public function rules(): array
    {
        return array_merge([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], $this->aturanItems());
    }
}
