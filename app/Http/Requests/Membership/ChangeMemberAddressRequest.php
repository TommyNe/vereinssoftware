<?php

namespace App\Http\Requests\Membership;

use App\Domain\Membership\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangeMemberAddressRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'street' => [
                'required',
                'string',
                'max:150',
            ],

            'house_number' => [
                'required',
                'string',
                'max:20',
            ],

            'postal_code' => [
                'required',
                'string',
                'max:20',
            ],

            'city' => [
                'required',
                'string',
                'max:150',
            ],

            'country_code' => [
                'required',
                'string',
                'size:2',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => strtoupper(
                (string) $this->input(
                    'country_code',
                    'DE',
                )
            ),
        ]);
    }
}
