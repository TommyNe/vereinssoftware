<?php

namespace App\Application\Contribution;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;

final readonly class CancelContributionCharge
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function handle(
        ContributionCharge $charge,
        string $reason,
        User $cancelledBy,
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
            === ContributionChargeStatus::Cancelled
        ) {
            throw new DomainException(
                'Die Forderung wurde bereits storniert.'
            );
        }

        if (
            trim($reason) === ''
        ) {
            throw new DomainException(
                'Für eine Stornierung ist eine Begründung erforderlich.'
            );
        }

        $charge->update([
            'status' => ContributionChargeStatus::Cancelled,

            'cancelled_at' => CarbonImmutable::now(),

            'cancellation_reason' => $reason,

            'cancelled_by' => $cancelledBy->getKey(),
        ]);
    }
}
