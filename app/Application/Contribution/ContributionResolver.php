<?php

namespace App\Application\Contribution;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Enums\MemberContributionOverrideType;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\MemberContributionOverride;
use App\Domain\Membership\Models\Member;
use Carbon\CarbonImmutable;
use DomainException;

final readonly class ContributionResolver
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function resolveAmount(
        Member $member,
        ContributionType $contributionType,
        CarbonImmutable $date,
    ): string {
        $this->ensureSameClub(
            $member,
            $contributionType,
        );

        $override =
            $this->findOverride(
                member: $member,
                contributionType: $contributionType,
                date: $date,
            );

        if ($override !== null) {
            return $this->resolveOverrideAmount(
                $override
            );
        }

        return $this->resolveStandardAmount(
            member: $member,
            contributionType: $contributionType,
            date: $date,
        );
    }

    private function findOverride(
        Member $member,
        ContributionType $contributionType,
        CarbonImmutable $date,
    ): ?MemberContributionOverride {
        return MemberContributionOverride::query()
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
                'valid_from',
                '<=',
                $date
            )
            ->where(
                function ($query) use (
                    $date
                ): void {
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
            )
            ->orderByDesc(
                'valid_from'
            )
            ->first();
    }

    private function resolveOverrideAmount(
        MemberContributionOverride $override,
    ): string {
        return match (MemberContributionOverrideType::from((string) $override->getRawOriginal('type'))) {
            MemberContributionOverrideType::Exempt => '0.00',

            MemberContributionOverrideType::FixedAmount => $override->amount
                ?? throw new DomainException(
                    'Für den individuellen Beitrag fehlt ein Betrag.'
                ),
        };
    }

    private function resolveStandardAmount(
        Member $member,
        ContributionType $contributionType,
        CarbonImmutable $date,
    ): string {
        $rate =
            ContributionRate::query()
                ->where(
                    'club_id',
                    $this->currentClub->id()
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
                    'valid_from',
                    '<=',
                    $date
                )
                ->where(
                    function ($query) use (
                        $date
                    ): void {
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
                )
                ->where(
                    function ($query) use (
                        $member
                    ): void {
                        $query
                            ->where(
                                'membership_type_id',
                                $member
                                    ->membership_type_id
                            )
                            ->orWhereNull(
                                'membership_type_id'
                            );
                    }
                )
                ->orderByRaw(
                    'membership_type_id IS NULL ASC'
                )
                ->first();

        if ($rate === null) {
            throw new DomainException(
                'Für dieses Mitglied ist kein gültiger Beitragssatz hinterlegt.'
            );
        }

        return (string) $rate->amount;
    }

    private function ensureSameClub(
        Member $member,
        ContributionType $contributionType,
    ): void {
        $clubId =
            $this->currentClub->id();

        if (
            (string) $member->club_id
            !== $clubId
            || (string) $contributionType->club_id
            !== $clubId
        ) {
            throw new DomainException(
                'Mitglied und Beitragsart gehören nicht zum aktuellen Verein.'
            );
        }
    }
}
