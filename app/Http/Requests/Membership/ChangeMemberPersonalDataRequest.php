<?php

namespace App\Http\Requests\Membership;

use App\Domain\Membership\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangeMemberPersonalDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $member = Member::query()
            ->forCurrentClub()
            ->find($this->route('member'));

        if ($member === null) {
            return false;
        }

        return $this->user()?->can(
            'update',
            $member,
        ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim(
                (string) $this->input('first_name')
            ),

            'last_name' => trim(
                (string) $this->input('last_name')
            ),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
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
                'before_or_equal:today',
            ],
        ];
    }
}
