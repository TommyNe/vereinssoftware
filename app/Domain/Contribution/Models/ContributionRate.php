<?php

namespace App\Domain\Contribution\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Membership\Models\MembershipType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContributionRate extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'contribution_type_id',
        'membership_type_id',
        'amount',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'valid_from' => 'immutable_date',

            'valid_until' => 'immutable_date',

            'is_active' => 'boolean',
        ];
    }

    public function scopeValidAt(
        Builder $query,
        CarbonInterface $date,
    ): Builder {
        $date = $date->copy()->startOfDay();

        return $query
            ->where(
                'valid_from',
                '<=',
                $date
            )
            ->where(
                function (
                    Builder $query
                ) use ($date): void {
                    $query
                        ->whereNull(
                            'valid_until'
                        )
                        ->orWhere(
                            'valid_until',
                            '>=',
                            $date
                        );
                }
            );
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(
            Club::class
        );
    }

    /**
     * @return BelongsTo<ContributionType, $this>
     */
    public function contributionType(): BelongsTo
    {
        return $this->belongsTo(
            ContributionType::class
        );
    }

    /**
     * @return BelongsTo<MembershipType, $this>
     */
    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(
            MembershipType::class
        );
    }
}
