<?php

namespace App\Http\Requests\Membership;

use App\Domain\Membership\Models\Member;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Member::class
        ) ?? false;
    }

    public function rules(): array
    {
        return [
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
