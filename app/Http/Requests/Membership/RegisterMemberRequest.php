<?php

namespace App\Http\Requests\Membership;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegisterMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Berechtigungen bauen wir im nächsten Schritt sauber ein.
        return true;
    }

    public function rules(): array
    {
        return [
            'club_id' => [
                'required',
                'uuid',
                Rule::exists('clubs', 'id'),
            ],

            'member_number' => [
                'required',
                'string',
                'max:50',
            ],

            'first_name' => [
                'required',
                'string',
                'max:150',
            ],

            'last_name' => [
                'required',
                'string',
                'max:150',
            ],

            'birth_date' => [
                'nullable',
                'date',
                'before:today',
            ],

            'joined_at' => [
                'required',
                'date',
            ],
        ];
    }
}
