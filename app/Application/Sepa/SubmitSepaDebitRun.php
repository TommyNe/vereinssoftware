<?php

namespace App\Application\Sepa;

use App\Application\Audit\AuditLogger;
use App\Application\Club\CurrentClub;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Sepa\Enums\SepaDebitEventType;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Enums\SepaDebitSubmissionStatus;
use App\Domain\Sepa\Enums\SepaSubmissionMethod;
use App\Domain\Sepa\Models\SepaDebitItemEvent;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Domain\Sepa\Models\SepaDebitSubmission;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class SubmitSepaDebitRun
{
    public function __construct(
        private CurrentClub $currentClub,
        private AuditLogger $audit,
    ) {}

    public function handle(
        SepaDebitRun $run,
        SepaSubmissionMethod $method,
        CarbonImmutable $submittedAt,
        ?string $bankReference,
        User $submittedBy,
    ): SepaDebitSubmission {
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
            !== SepaDebitRunStatus::Exported
        ) {
            throw new DomainException(
                'Nur exportierte SEPA-Läufe können als eingereicht markiert werden.'
            );
        }

        if (
            $run->xml_storage_path === null
            || $run->xml_sha256 === null
        ) {
            throw new DomainException(
                'Für diesen Lastschriftlauf existiert keine vollständige Exportdatei.'
            );
        }

        return DB::transaction(
            function () use (
                $run,
                $method,
                $submittedAt,
                $bankReference,
                $submittedBy,
            ): SepaDebitSubmission {
                $lockedRun =
                    SepaDebitRun::query()
                        ->whereKey(
                            $run->getKey()
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $lockedRun->submission()
                        ->exists()
                ) {
                    throw new DomainException(
                        'Der Lastschriftlauf wurde bereits als eingereicht erfasst.'
                    );
                }

                $submission =
                    SepaDebitSubmission::query()
                        ->create([
                            'club_id' => $lockedRun->club_id,

                            'sepa_debit_run_id' => $lockedRun->getKey(),

                            'status' => SepaDebitSubmissionStatus::Submitted,

                            'submission_method' => $method,

                            'bank_reference' => $bankReference !== null
                                    ? trim(
                                        $bankReference
                                    )
                                    : null,

                            'submitted_at' => $submittedAt,

                            'submitted_by' => $submittedBy
                                ->getKey(),
                        ]);

                $lockedRun->update([
                    'status' => SepaDebitRunStatus::Submitted,
                ]);

                $items = $lockedRun->items()
                    ->where('status', SepaDebitItemStatus::Prepared->value)
                    ->get();

                foreach ($items as $item) {
                    $item->update([
                        'status' => SepaDebitItemStatus::Submitted,
                    ]);

                    SepaDebitItemEvent::query()->create([
                        'club_id' => $lockedRun->club_id,
                        'sepa_debit_item_id' => $item->getKey(),
                        'type' => SepaDebitEventType::Submitted,
                        'occurred_at' => $submittedAt,
                        'bank_reference' => $bankReference,
                        'source' => 'manual',
                        'recorded_by' => $submittedBy->getKey(),
                    ]);
                }

                $this->audit->log(AuditAction::SepaDebitRunSubmitted, $lockedRun, user: $submittedBy, properties: [
                    'run_id' => $lockedRun->getKey(),
                ]);

                return $submission;
            }
        );
    }
}
