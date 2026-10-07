<?php

namespace App\Application\Sepa;

use App\Application\Club\CurrentClub;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CancelSepaDebitRun
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        SepaDebitRun $run,
        string $reason,
        User $cancelledBy,
    ): void {
        if (
            (string) $run->club_id
            !== $this->currentClub->id()
        ) {
            throw new DomainException(
                'Der Lastschriftlauf gehört nicht zum aktuellen Verein.'
            );
        }

        if (
            $run->status
            === SepaDebitRunStatus::Exported
        ) {
            throw new DomainException(
                'Ein bereits exportierter Lastschriftlauf kann nicht auf diesem Weg storniert werden.'
            );
        }

        if (
            $run->status
            === SepaDebitRunStatus::Cancelled
        ) {
            throw new DomainException(
                'Der Lastschriftlauf ist bereits storniert.'
            );
        }

        if (
            trim($reason) === ''
        ) {
            throw new DomainException(
                'Eine Begründung ist erforderlich.'
            );
        }

        DB::transaction(function () use ($run, $reason, $cancelledBy): void {
            $run->items()
                ->where('status', SepaDebitItemStatus::Prepared->value)
                ->update([
                    'status' => SepaDebitItemStatus::Cancelled->value,
                ]);

            $run->update([
                'status' => SepaDebitRunStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => trim($reason),
                'cancelled_by' => $cancelledBy->getKey(),
            ]);
        });
    }
}
