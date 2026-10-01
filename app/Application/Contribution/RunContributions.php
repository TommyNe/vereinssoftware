<?php

namespace App\Application\Contribution;

use App\Application\Audit\AuditLogger;
use App\Application\Club\CurrentClub;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Enums\ContributionRunStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionRun;
use App\Domain\Contribution\Models\ContributionRunError;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Throwable;

final readonly class RunContributions
{
    public function __construct(
        private CurrentClub $currentClub,
        private ContributionResolver $resolver,
        private AuditLogger $audit,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        ContributionType $contributionType,
        CarbonImmutable $calculationDate,
        CarbonImmutable $periodFrom,
        ?CarbonImmutable $periodUntil,
        CarbonImmutable $dueDate,
        string $description,
        User $createdBy,
    ): ContributionRun {
        $this->validateInput(
            contributionType: $contributionType,
            calculationDate: $calculationDate,
            periodFrom: $periodFrom,
            periodUntil: $periodUntil,
            dueDate: $dueDate,
        );

        $run =
            ContributionRun::query()
                ->create([
                    'club_id' => $this->currentClub->id(),

                    'contribution_type_id' => $contributionType
                        ->getKey(),

                    'status' => ContributionRunStatus::Pending,

                    'calculation_date' => $calculationDate,

                    'period_from' => $periodFrom,

                    'period_until' => $periodUntil,

                    'due_date' => $dueDate,

                    'description' => $description,

                    'created_by' => $createdBy->getKey(),
                ]);

        $this->audit->log(
            AuditAction::ContributionRunStarted,
            $run,
            $createdBy,
            [
                'contribution_type_id' => $contributionType->getKey(),
                'calculation_date' => $calculationDate->toDateString(),
                'period_from' => $periodFrom->toDateString(),
                'period_until' => $periodUntil?->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'description' => $description,
            ],
        );

        return $this->process(
            $run,
            $createdBy,
        );
    }

    private function validateInput(
        ContributionType $contributionType,
        CarbonImmutable $calculationDate,
        CarbonImmutable $periodFrom,
        ?CarbonImmutable $periodUntil,
        CarbonImmutable $dueDate,
    ): void {
        if (
            (string) $contributionType->club_id
            !== $this->currentClub->id()
        ) {
            throw new DomainException(
                'Die Beitragsart gehört nicht zum aktuellen Verein.'
            );
        }

        if (! $contributionType->is_active) {
            throw new DomainException(
                'Die Beitragsart ist nicht aktiv.'
            );
        }

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

    /**
     * @throws Throwable
     */
    private function process(
        ContributionRun $run,
        User $createdBy,
    ): ContributionRun {
        $run->update([
            'status' => ContributionRunStatus::Running,

            'started_at' => now(),
        ]);

        $result =
            new ContributionRunResult;

        try {
            Member::query()
                ->forCurrentClub()

                ->whereIn(
                    'status',
                    [
                        MembershipStatus::Active->value,
                        MembershipStatus::Suspended->value,
                    ]
                )

                ->whereDate(
                    'joined_at',
                    '<=',
                    $run->calculation_date
                )

                ->orderBy('uuid')

                ->chunkById(
                    100,
                    function ($members) use (
                        $run,
                        $result,
                        $createdBy,
                    ): void {
                        foreach (
                            $members as $member
                        ) {
                            $this->processMember(
                                run: $run,
                                member: $member,
                                result: $result,
                                createdBy: $createdBy,
                            );
                        }
                    },
                    'uuid',
                    'uuid',
                );

            $run->update([
                'status' => $result->errorsCount > 0
                        ? ContributionRunStatus::CompletedWithErrors
                        : ContributionRunStatus::Completed,

                'members_processed' => $result->membersProcessed,

                'charges_created' => $result->chargesCreated,

                'members_exempt' => $result->membersExempt,

                'duplicates_skipped' => $result->duplicatesSkipped,

                'errors_count' => $result->errorsCount,

                'total_amount' => $result->totalAmount,

                'finished_at' => now(),
            ]);

            $this->audit->log(
                AuditAction::ContributionRunCompleted,
                $run,
                $createdBy,
                properties: [
                    'status' => $run->status->value,
                    'members_processed' => $result->membersProcessed,
                    'charges_created' => $result->chargesCreated,
                    'members_exempt' => $result->membersExempt,
                    'duplicates_skipped' => $result->duplicatesSkipped,
                    'errors_count' => $result->errorsCount,
                    'total_amount' => $result->totalAmount,
                ],
            );
        } catch (Throwable $exception) {
            $run->update([
                'status' => ContributionRunStatus::Failed,

                'members_processed' => $result->membersProcessed,
                'charges_created' => $result->chargesCreated,
                'members_exempt' => $result->membersExempt,
                'duplicates_skipped' => $result->duplicatesSkipped,
                'errors_count' => $result->errorsCount,
                'total_amount' => $result->totalAmount,

                'finished_at' => now(),
            ]);

            $this->audit->log(AuditAction::ContributionRunFailed, $run, $createdBy, [
                'status' => ContributionRunStatus::Failed->value,
                'exception' => $exception::class,
                'members_processed' => $result->membersProcessed,
                'charges_created' => $result->chargesCreated,
                'members_exempt' => $result->membersExempt,
                'duplicates_skipped' => $result->duplicatesSkipped,
                'errors_count' => $result->errorsCount,
                'total_amount' => $result->totalAmount,
            ]);

            throw $exception;
        }

        return $run->refresh();
    }

    private function processMember(
        ContributionRun $run,
        Member $member,
        ContributionRunResult $result,
        User $createdBy,
    ): void {
        $result->membersProcessed++;

        try {
            if (
                $this->chargeAlreadyExists(
                    run: $run,
                    member: $member,
                )
            ) {
                $result->duplicatesSkipped++;

                return;
            }

            $amount =
                $this->resolver
                    ->resolveAmount(
                        member: $member,
                        contributionType: $run->contributionType,
                        date: $run->calculation_date,
                    );

            if (
                bccomp(
                    $amount,
                    '0.00',
                    2
                ) === 0
            ) {
                $result->membersExempt++;
            }

            $charge =
                ContributionCharge::query()
                    ->create([
                        'club_id' => $run->club_id,

                        'member_id' => $member->getKey(),

                        'contribution_type_id' => $run
                            ->contribution_type_id,

                        'contribution_run_id' => $run->getKey(),

                        'status' => ContributionChargeStatus::Open,

                        'amount' => $amount,

                        'description' => $run->description,

                        'period_from' => $run->period_from,

                        'period_until' => $run->period_until,

                        'due_date' => $run->due_date,

                        'created_by' => $run->created_by,
                    ]);

            $this->audit->log(
                AuditAction::ContributionChargeCreated,
                $charge,
                $createdBy,
                properties: [
                    'contribution_run_id' => $run->getKey(),
                    'amount' => $charge->amount,
                ],
            );

            $result->chargesCreated++;

            $result->totalAmount =
                bcadd(
                    $result->totalAmount,
                    $charge->amount,
                    2
                );
        } catch (Throwable $exception) {
            $result->errorsCount++;

            ContributionRunError::query()->create([
                'contribution_run_id' => $run->getKey(),
                'member_id' => $member->getKey(),
                'message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    1000,
                ),
            ]);

            report(
                $exception
            );
        }
    }

    private function chargeAlreadyExists(
        ContributionRun $run,
        Member $member,
    ): bool {
        return ContributionCharge::query()
            ->where(
                'club_id',
                $run->club_id
            )
            ->where(
                'member_id',
                $member->getKey()
            )
            ->where(
                'contribution_type_id',
                $run->contribution_type_id
            )
            ->where(
                'period_from',
                $run->period_from
            )
            ->where(
                function ($query) use (
                    $run
                ): void {
                    if (
                        $run->period_until
                        === null
                    ) {
                        $query->whereNull(
                            'period_until'
                        );

                        return;
                    }

                    $query->where(
                        'period_until',
                        $run->period_until
                    );
                }
            )
            ->where(
                'status',
                '!=',
                ContributionChargeStatus::Cancelled->value
            )
            ->exists();
    }
}
