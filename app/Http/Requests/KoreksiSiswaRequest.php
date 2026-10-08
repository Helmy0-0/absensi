<?php

namespace App\Http\Requests;

class KoreksiSiswaRequest extends ItemsSiswaRequest
{
    public function rules(): array
    {
        return array_merge([
            'catatan_koreksi' => ['required', 'string', 'min:5', 'max:500'],
        ], $this->aturanItems());
    }
}
