<?php

namespace App\Application\Sepa;

use App\Application\Audit\AuditLogger;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Models\SepaDebitRun;

final class RefreshSepaDebitRunStatus
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function handle(
        SepaDebitRun $run,
    ): void {
        $counts =
            $run->items()
                ->selectRaw(
                    'status, COUNT(*) as aggregate'
                )
                ->groupBy('status')
                ->pluck(
                    'aggregate',
                    'status'
                );

        $accepted =
            (int) (
                $counts[
                SepaDebitItemStatus::Accepted
                    ->value
                ] ?? 0
            );

        $rejected =
            (int) (
                $counts[
                SepaDebitItemStatus::Rejected
                    ->value
                ] ?? 0
            );

        $submitted =
            (int) (
                $counts[
                SepaDebitItemStatus::Submitted
                    ->value
                ] ?? 0
            );

        $total =
            $run->items()
                ->count();

        if (
            $total > 0
            && $accepted === $total
        ) {
            if ($run->status === SepaDebitRunStatus::Accepted) {
                return;
            }

            $run->update([
                'status' => SepaDebitRunStatus::Accepted,
            ]);

            if ($run->wasChanged('status')) {
                $this->audit->log(AuditAction::SepaDebitRunAccepted, $run, properties: [
                    'run_id' => $run->getKey(),
                ]);
            }

            return;
        }

        if (
            $total > 0
            && $rejected === $total
        ) {
            if ($run->status === SepaDebitRunStatus::Rejected) {
                return;
            }

            $run->update([
                'status' => SepaDebitRunStatus::Rejected,
            ]);

            if ($run->wasChanged('status')) {
                $this->audit->log(AuditAction::SepaDebitRunRejected, $run, properties: [
                    'run_id' => $run->getKey(),
                ]);
            }

            return;
        }

        if (
            $accepted > 0
            && $rejected > 0
        ) {
            $run->update([
                'status' => SepaDebitRunStatus::PartiallyAccepted,
            ]);

            return;
        }

        if ($submitted > 0) {
            $run->update([
                'status' => SepaDebitRunStatus::Submitted,
            ]);
        }
    }
}
