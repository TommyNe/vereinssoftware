<?php

namespace App\Domain\Contribution\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\MemberContributionOverrideType;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MemberContributionOverride extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'member_id',
        'contribution_type_id',
        'type',
        'amount',
        'valid_from',
        'valid_until',
        'reason',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => MemberContributionOverrideType::class,

            'amount' => 'decimal:2',

            'valid_from' => 'immutable_date',

            'valid_until' => 'immutable_date',

            'is_active' => 'boolean',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(
            Club::class
        );
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(
            Member::class
        );
    }

    public function contributionType(): BelongsTo
    {
        return $this->belongsTo(
            ContributionType::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
