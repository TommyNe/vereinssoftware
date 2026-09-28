<?php

namespace App\Application\Contribution;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Enums\MemberContributionOverrideType;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\MemberContributionOverride;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;

final readonly class MemberContributionOverrideManager
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function create(
        Member $member,
        string $contributionTypeId,
        MemberContributionOverrideType $type,
        ?string $amount,
        CarbonImmutable $validFrom,
        ?CarbonImmutable $validUntil,
        ?string $reason,
        User $createdBy,
    ): MemberContributionOverride {
        $clubId =
            $this->currentClub->id();

        if (
            (string) $member->club_id
            !== $clubId
        ) {
            throw new DomainException(
                'Das Mitglied gehört nicht zum aktuellen Verein.'
            );
        }

        $contributionType =
            ContributionType::query()
                ->whereKey(
                    $contributionTypeId
                )
                ->where(
                    'club_id',
                    $clubId
                )
                ->where(
                    'is_active',
                    true
                )
                ->firstOrFail();

        if (
            $validUntil !== null
            && $validUntil->lt(
                $validFrom
            )
        ) {
            throw new DomainException(
                'Das Enddatum darf nicht vor dem Startdatum liegen.'
            );
        }

        if (
            $type
            === MemberContributionOverrideType::FixedAmount
            && $amount === null
        ) {
            throw new DomainException(
                'Für einen individuellen Beitrag muss ein Betrag angegeben werden.'
            );
        }

        if (
            $type
            === MemberContributionOverrideType::Exempt
        ) {
            $amount = null;
        }

        $this->ensureNoOverlap(
            member: $member,
            contributionType: $contributionType,
            validFrom: $validFrom,
            validUntil: $validUntil,
        );

        return MemberContributionOverride::query()
            ->create([
                'club_id' => $clubId,

                'member_id' => $member->getKey(),

                'contribution_type_id' => $contributionType
                    ->getKey(),

                'type' => $type,

                'amount' => $amount,

                'valid_from' => $validFrom,

                'valid_until' => $validUntil,

                'reason' => $reason,

                'is_active' => true,

                'created_by' => $createdBy->getKey(),
            ]);
    }

    private function ensureNoOverlap(
        Member $member,
        ContributionType $contributionType,
        CarbonImmutable $validFrom,
        ?CarbonImmutable $validUntil,
    ): void {
        $query =
            MemberContributionOverride::query()
                ->where(
                    'club_id',
                    $this->currentClub->id()
                )
                ->where(
                    'member_id',
                    $member->getKey()
                )
                ->where(
                    'contribution_type_id',
                    $contributionType->getKey()
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    function ($query) use (
                        $validFrom
                    ): void {
                        $query
                            ->whereNull(
                                'valid_until'
                            )
                            ->orWhere(
                                'valid_until',
                                '>=',
                                $validFrom
                            );
                    }
                );

        if ($validUntil !== null) {
            $query->where(
                'valid_from',
                '<=',
                $validUntil
            );
        }

        if ($query->exists()) {
            throw new DomainException(
                'Für diesen Zeitraum existiert bereits eine individuelle Beitragsregel.'
            );
        }
    }
}
