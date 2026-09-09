<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class SwitchClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'club_id' => ['required', 'uuid'],
        ];
    }
}
