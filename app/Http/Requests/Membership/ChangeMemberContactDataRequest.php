<?php

namespace App\Http\Requests\Membership;

use App\Domain\Membership\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangeMemberContactDataRequest extends FormRequest
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
            'email' => $this->normalizeNullable(
                $this->input('email')
            ),
            'phone' => $this->normalizeNullable(
                $this->input('phone')
            ),
            'mobile' => $this->normalizeNullable(
                $this->input('mobile')
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
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    private function normalizeNullable(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
