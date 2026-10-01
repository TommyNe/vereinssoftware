<?php

namespace App\Application\Contribution;

use App\Application\Audit\AuditLogger;
use App\Application\Club\CurrentClub;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use Carbon\CarbonImmutable;
use DomainException;

final readonly class MarkContributionChargePaid
{
    public function __construct(
        private CurrentClub $currentClub,
        private AuditLogger $audit,
    ) {}

    public function handle(
        ContributionCharge $charge,
        CarbonImmutable $paidAt,
    ): void {
        if (
            (string) $charge->club_id
            !== $this->currentClub->id()
        ) {
            throw new DomainException(
                'Die Forderung gehört nicht zum aktuellen Verein.'
            );
        }

        if (
            $charge->status
            !== ContributionChargeStatus::Open
        ) {
            throw new DomainException(
                'Nur offene Forderungen können als bezahlt markiert werden.'
            );
        }

        $charge->update([
            'status' => ContributionChargeStatus::Paid,

            'paid_at' => $paidAt,
        ]);

        $this->audit->log(
            AuditAction::ContributionChargePaid,
            $charge,
            properties: [
                'paid_at' => $paidAt->toIso8601String(),
            ],
        );
    }
}
