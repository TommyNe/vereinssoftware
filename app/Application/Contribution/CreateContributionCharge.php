<?php

namespace App\Application\Contribution;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class CreateContributionCharge
{
    public function __construct(
        private CurrentClub $currentClub,
        private ContributionResolver $resolver,
    ) {}

    public function handle(
        Member $member,
        ContributionType $contributionType,
        CarbonImmutable $calculationDate,
        CarbonImmutable $periodFrom,
        ?CarbonImmutable $periodUntil,
        CarbonImmutable $dueDate,
        string $description,
        User $createdBy,
    ): ContributionCharge {
        $this->ensureSameClub(
            $member,
            $contributionType,
        );

        $this->validatePeriod(
            periodFrom: $periodFrom,
            periodUntil: $periodUntil,
            dueDate: $dueDate,
        );

        $amount =
            $this->resolver->resolveAmount(
                member: $member,
                contributionType: $contributionType,
                date: $calculationDate,
            );

        return DB::transaction(
            function () use (
                $member,
                $contributionType,
                $amount,
                $periodFrom,
                $periodUntil,
                $dueDate,
                $description,
                $createdBy,
            ): ContributionCharge {
                $this->ensureNoDuplicate(
                    member: $member,
                    contributionType: $contributionType,
                    periodFrom: $periodFrom,
                    periodUntil: $periodUntil,
                );

                return ContributionCharge::query()
                    ->create([
                        'club_id' => $this->currentClub->id(),

                        'member_id' => $member->getKey(),

                        'contribution_type_id' => $contributionType
                            ->getKey(),

                        'status' => ContributionChargeStatus::Open,

                        'amount' => $amount,

                        'description' => $description,

                        'period_from' => $periodFrom,

                        'period_until' => $periodUntil,

                        'due_date' => $dueDate,

                        'created_by' => $createdBy
                            ->getKey(),
                    ]);
            }
        );
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
                'Mitglied und Beitragsart müssen zum aktuellen Verein gehören.'
            );
        }
    }

    private function validatePeriod(
        CarbonImmutable $periodFrom,
        ?CarbonImmutable $periodUntil,
        CarbonImmutable $dueDate,
    ): void {
        if (
            $periodUntil !== null
            && $periodUntil->lt(
                $periodFrom
            )
        ) {
            throw new DomainException(
                'Das Periodenende darf nicht vor dem Periodenbeginn liegen.'
            );
        }

        if (
            $dueDate->lt(
                $periodFrom
            )
        ) {
            throw new DomainException(
                'Das Fälligkeitsdatum darf nicht vor dem Periodenbeginn liegen.'
            );
        }
    }

    private function ensureNoDuplicate(
        Member $member,
        ContributionType $contributionType,
        CarbonImmutable $periodFrom,
        ?CarbonImmutable $periodUntil,
    ): void {
        $exists =
            ContributionCharge::query()
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
                    'period_from',
                    $periodFrom
                )
                ->where(
                    function ($query) use (
                        $periodUntil
                    ): void {
                        if (
                            $periodUntil === null
                        ) {
                            $query->whereNull(
                                'period_until'
                            );

                            return;
                        }

                        $query->where(
                            'period_until',
                            $periodUntil
                        );
                    }
                )
                ->where(
                    'status',
                    '!=',
                    ContributionChargeStatus::Cancelled->value
                )
                ->exists();

        if ($exists) {
            throw new DomainException(
                'Für diesen Zeitraum wurde bereits eine Forderung erzeugt.'
            );
        }
    }
}
