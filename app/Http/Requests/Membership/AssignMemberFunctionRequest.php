<?php

namespace App\Http\Requests\Membership;

use App\Domain\Membership\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignMemberFunctionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $member = Member::query()
            ->forCurrentClub()
            ->find(
                $this->route('member')
            );

        if ($member === null) {
            return false;
        }

        return $this->user()?->can(
            'manageFunctions',
            $member,
        ) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'club_function_id' => [
                'required',
                'uuid',
            ],

            'valid_from' => [
                'required',
                'date',
            ],
        ];
    }
}
