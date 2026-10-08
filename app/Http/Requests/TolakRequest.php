<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TolakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan_validasi' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['catatan_validasi' => 'alasan penolakan'];
    }
}
