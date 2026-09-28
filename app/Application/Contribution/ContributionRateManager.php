<?php

namespace App\Application\Contribution;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Models\MembershipType;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Builder;

final readonly class ContributionRateManager
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function create(
        string $contributionTypeId,
        ?string $membershipTypeId,
        string $amount,
        CarbonImmutable $validFrom,
        ?CarbonImmutable $validUntil,
    ): ContributionRate {
        $validFrom = $validFrom->startOfDay();
        $validUntil = $validUntil?->startOfDay();

        $clubId =
            $this->currentClub->id();

        $type =
            ContributionType::query()
                ->whereKey(
                    $contributionTypeId
                )
                ->where(
                    'club_id',
                    $clubId
                )
                ->firstOrFail();

        $membershipType = null;

        if (
            $membershipTypeId !== null
        ) {
            $membershipType =
                MembershipType::query()
                    ->whereKey(
                        $membershipTypeId
                    )
                    ->where(
                        'club_id',
                        $clubId
                    )
                    ->firstOrFail();
        }

        if (
            $validUntil !== null
            && $validUntil->lt(
                $validFrom
            )
        ) {
            throw new DomainException(
                'Das Gültig-bis-Datum darf nicht vor dem Gültig-ab-Datum liegen.'
            );
        }

        $overlappingRates = ContributionRate::query()
            ->where('club_id', $clubId)
            ->where('contribution_type_id', $type->getKey())
            ->where('membership_type_id', $membershipType?->getKey())
            ->where(function (Builder $query) use ($validFrom): void {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $validFrom);
            });

        if ($validUntil !== null) {
            $overlappingRates->where('valid_from', '<=', $validUntil);
        }

        if ($overlappingRates->exists()) {
            throw new DomainException(
                'Die Gültigkeitszeiträume dürfen sich nicht überschneiden.'
            );
        }

        return ContributionRate::query()
            ->create([
                'club_id' => $clubId,

                'contribution_type_id' => $type->getKey(),

                'membership_type_id' => $membershipType?->getKey(),

                'amount' => $amount,

                'valid_from' => $validFrom,

                'valid_until' => $validUntil,

                'is_active' => true,
            ]);
    }
}
