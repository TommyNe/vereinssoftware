<?php

namespace App\Application\Sepa;

use App\Application\Audit\AuditLogger;
use App\Application\Club\CurrentClub;
use App\Application\Contribution\CreatePaymentFromSettledSepaItem;
use App\Application\Contribution\ReversePaymentForSepaReturn;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Sepa\Enums\SepaDebitEventType;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Domain\Sepa\Models\SepaDebitItemEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class RecordSepaDebitItemEvent
{
    public function __construct(
        private CurrentClub $currentClub,
        private AuditLogger $audit,
        private CreatePaymentFromSettledSepaItem $createPayment,
        private ReversePaymentForSepaReturn $reversePayment,
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(
        SepaDebitItem $item,
        SepaDebitEventType $type,
        CarbonImmutable $occurredAt,
        ?string $reasonCode,
        ?string $reasonText,
        ?string $bankReference,
        string $source,
        ?User $recordedBy,
    ): SepaDebitItemEvent {
        if (
            (string) $item->club_id
            !== $this->currentClub->id()
        ) {
            throw new DomainException(
                'Die Lastschriftposition gehört nicht zum aktuellen Verein.'
            );
        }

        $newStatus =
            $this->statusForEvent(
                $type
            );

        $this->assertTransitionAllowed(
            current: $item->status,
            target: $newStatus,
        );

        return DB::transaction(
            function () use (
                $item,
                $type,
                $newStatus,
                $occurredAt,
                $reasonCode,
                $reasonText,
                $bankReference,
                $source,
                $recordedBy,
            ): SepaDebitItemEvent {
                $lockedItem =
                    SepaDebitItem::query()
                        ->whereKey(
                            $item->getKey()
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->assertTransitionAllowed($lockedItem->status, $newStatus);

                $event =
                    SepaDebitItemEvent::query()
                        ->create([
                            'club_id' => $lockedItem
                                ->club_id,

                            'sepa_debit_item_id' => $lockedItem
                                ->getKey(),

                            'type' => $type,

                            'occurred_at' => $occurredAt,

                            'reason_code' => filled(
                                $reasonCode
                            )
                                    ? strtoupper(
                                        trim(
                                            $reasonCode
                                        )
                                    )
                                    : null,

                            'reason_text' => filled(
                                $reasonText
                            )
                                    ? trim(
                                        $reasonText
                                    )
                                    : null,

                            'bank_reference' => filled(
                                $bankReference
                            )
                                    ? trim(
                                        $bankReference
                                    )
                                    : null,

                            'source' => $source,

                            'recorded_by' => $recordedBy
                                ?->getKey(),
                        ]);

                $lockedItem->update([
                    'status' => $newStatus,
                ]);

                if ($type === SepaDebitEventType::Settled) {
                    $this->createPayment->handle($lockedItem, $occurredAt, $recordedBy);
                } elseif ($type === SepaDebitEventType::Returned) {
                    $this->reversePayment->handle(
                        $lockedItem,
                        $event->reason_text ?? $event->reason_code ?? 'SEPA-Rücklastschrift',
                        $recordedBy ?? throw new DomainException('Für Rücklastschriften muss ein Benutzer vorhanden sein.'),
                    );
                }

                $auditAction = match ($type) {
                    SepaDebitEventType::Submitted => null,
                    SepaDebitEventType::Accepted => AuditAction::SepaDebitItemAccepted,
                    SepaDebitEventType::Rejected => AuditAction::SepaDebitItemRejected,
                    SepaDebitEventType::Settled => AuditAction::SepaDebitItemSettled,
                    SepaDebitEventType::Returned => AuditAction::SepaDebitItemReturned,
                    SepaDebitEventType::Refunded => AuditAction::SepaDebitItemRefunded,
                };

                if ($auditAction !== null) {
                    $this->audit->log($auditAction, $lockedItem, user: $recordedBy, properties: [
                        'run_id' => $lockedItem->sepa_debit_run_id,
                        'item_id' => $lockedItem->getKey(),
                        'end_to_end_id' => $lockedItem->end_to_end_id,
                        'reason_code' => $event->reason_code,
                    ]);
                }

                return $event;
            }
        );
    }

    private function statusForEvent(
        SepaDebitEventType $type,
    ): SepaDebitItemStatus {
        return match ($type) {
            SepaDebitEventType::Submitted => SepaDebitItemStatus::Submitted,

            SepaDebitEventType::Accepted => SepaDebitItemStatus::Accepted,

            SepaDebitEventType::Rejected => SepaDebitItemStatus::Rejected,

            SepaDebitEventType::Settled => SepaDebitItemStatus::Settled,

            SepaDebitEventType::Returned => SepaDebitItemStatus::Returned,

            SepaDebitEventType::Refunded => SepaDebitItemStatus::Refunded,
        };
    }

    private function assertTransitionAllowed(
        SepaDebitItemStatus $current,
        SepaDebitItemStatus $target,
    ): void {
        $allowed = match ($current) {
            SepaDebitItemStatus::Prepared => [
                SepaDebitItemStatus::Submitted,
                SepaDebitItemStatus::Cancelled,
            ],

            SepaDebitItemStatus::Submitted => [
                SepaDebitItemStatus::Accepted,
                SepaDebitItemStatus::Rejected,
            ],

            SepaDebitItemStatus::Accepted => [
                SepaDebitItemStatus::Settled,
                SepaDebitItemStatus::Rejected,
            ],

            SepaDebitItemStatus::Settled => [
                SepaDebitItemStatus::Returned,
                SepaDebitItemStatus::Refunded,
            ],

            default => [],
        };

        if (! in_array(
            $target,
            $allowed,
            true
        )) {
            throw new DomainException(
                sprintf(
                    'Der SEPA-Status darf nicht von "%s" nach "%s" geändert werden.',
                    $current->label(),
                    $target->label(),
                )
            );
        }
    }
}
