<?php

namespace App\Http\Requests\Membership;

use App\Application\Club\CurrentClub;
use App\Domain\Membership\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeMembershipTypeRequest extends FormRequest
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
        $currentClub = app(CurrentClub::class);

        return [
            'membership_type_id' => [
                'required',
                'uuid',

                Rule::exists(
                    'membership_types',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'club_id',
                            $currentClub->id()
                        )
                        ->where(
                            'is_active',
                            true
                        )
                ),
            ],
        ];
    }
}
