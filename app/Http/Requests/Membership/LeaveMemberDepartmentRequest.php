<?php

namespace App\Http\Requests\Membership;

use App\Domain\Membership\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LeaveMemberDepartmentRequest extends FormRequest
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
            'manageDepartments',
            $member,
        ) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'left_at' => [
                'required',
                'date',
            ],
        ];
    }
}
