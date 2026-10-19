<?php

namespace App\Application\Contribution;

use App\Application\Audit\AuditLogger;
use App\Application\Club\CurrentClub;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Enums\PaymentMethod;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Contribution\Models\PaymentAllocation;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class PaymentManager
{
    public function __construct(
        private CurrentClub $currentClub,
        private PaymentBalanceResolver $balanceResolver,
        private SyncContributionChargePaymentStatus $chargeStatusSync,
        private AuditLogger $audit,
    ) {}

    public function create(
        Member $member,
        string $amount,
        PaymentMethod $method,
        CarbonImmutable $bookingDate,
        ?CarbonImmutable $valueDate,
        ?string $reference,
        ?string $notes,
        User $createdBy,
        ?string $sourceType = null,
        ?string $sourceId = null,
    ): Payment {
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

        if (
            bccomp(
                $amount,
                '0.00',
                2
            ) <= 0
        ) {
            throw new DomainException(
                'Der Zahlungsbetrag muss größer als 0 sein.'
            );
        }

        return DB::transaction(function () use (
            $clubId,
            $member,
            $amount,
            $method,
            $bookingDate,
            $valueDate,
            $reference,
            $notes,
            $sourceType,
            $sourceId,
            $createdBy,
        ): Payment {
            $payment = Payment::query()
                ->create([
                    'club_id' => $clubId,

                    'member_id' => $member->getKey(),

                    'status' => PaymentStatus::Booked,

                    'method' => $method,

                    'amount' => $amount,

                    'currency' => 'EUR',

                    'booking_date' => $bookingDate,

                    'value_date' => $valueDate,

                    'reference' => $reference,

                    'notes' => $notes,

                    'source_type' => $sourceType,

                    'source_id' => $sourceId,

                    'created_by' => $createdBy->getKey(),
                ]);

            $this->audit->log(AuditAction::PaymentCreated, $payment, $createdBy, [
                'payment_id' => $payment->getKey(),
                'member_id' => $payment->member_id,
                'amount' => $payment->amount,
                'method' => $payment->method->value,
            ]);

            return $payment;
        });
    }

    /**
     * @throws \Throwable
     */
    public function allocate(
        Payment $payment,
        ContributionCharge $charge,
        string $amount,
    ): PaymentAllocation {
        return DB::transaction(function () use ($payment, $charge, $amount): PaymentAllocation {
            $lockedPayment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedCharge = ContributionCharge::query()
                ->whereKey($charge->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $clubId =
                $this->currentClub->id();

            if (
                (string) $lockedPayment->club_id
                !== $clubId
                || (string) $lockedCharge->club_id
                !== $clubId
            ) {
                throw new DomainException(
                    'Zahlung und Forderung müssen zum aktuellen Verein gehören.'
                );
            }

            if (
                (string) $lockedPayment->member_id
                !== (string) $lockedCharge->member_id
            ) {
                throw new DomainException(
                    'Zahlung und Forderung gehören nicht zum selben Mitglied.'
                );
            }

            if (
                $lockedPayment->status
                !== PaymentStatus::Booked
            ) {
                throw new DomainException(
                    'Nur aktive Zahlungen können zugeordnet werden.'
                );
            }

            if ($lockedCharge->status === ContributionChargeStatus::Cancelled) {
                throw new DomainException('Stornierte Forderungen können keiner Zahlung zugeordnet werden.');
            }

            if (
                bccomp(
                    $amount,
                    '0.00',
                    2
                ) <= 0
            ) {
                throw new DomainException(
                    'Der Zuordnungsbetrag muss größer als 0 sein.'
                );
            }

            $paymentRemaining =
                $this->balanceResolver
                    ->unallocatedAmount(
                        $lockedPayment
                    );

            if (
                bccomp(
                    $amount,
                    $paymentRemaining,
                    2
                ) > 0
            ) {
                throw new DomainException(
                    'Die Zuordnung ist größer als der noch verfügbare Zahlungsbetrag.'
                );
            }

            $chargeRemaining =
                $this->balanceResolver
                    ->outstandingAmount(
                        $lockedCharge
                    );

            if (
                bccomp(
                    $amount,
                    $chargeRemaining,
                    2
                ) > 0
            ) {
                throw new DomainException(
                    'Die Zuordnung ist größer als der offene Forderungsbetrag.'
                );
            }

            $allocation = PaymentAllocation::query()
                ->create([
                    'club_id' => $clubId,

                    'payment_id' => $lockedPayment->getKey(),

                    'contribution_charge_id' => $lockedCharge->getKey(),

                    'amount' => $amount,
                ]);

            $this->chargeStatusSync->handle($lockedCharge);

            $this->audit->log(AuditAction::PaymentAllocated, $allocation, properties: [
                'payment_id' => $lockedPayment->getKey(),
                'charge_id' => $lockedCharge->getKey(),
                'amount' => $allocation->amount,
            ]);

            return $allocation;
        });
    }

    public function reverse(
        Payment $payment,
        string $reason,
        User $reversedBy,
    ): void {
        DB::transaction(function () use ($payment, $reason, $reversedBy): void {
            $lockedPayment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (string) $lockedPayment->club_id
                !== $this->currentClub->id()
            ) {
                throw new DomainException(
                    'Die Zahlung gehört nicht zum aktuellen Verein.'
                );
            }

            if (
                $lockedPayment->status
                !== PaymentStatus::Booked
            ) {
                throw new DomainException(
                    'Nur gebuchte Zahlungen können storniert werden.'
                );
            }

            if (
                trim($reason) === ''
            ) {
                throw new DomainException(
                    'Eine Begründung ist erforderlich.'
                );
            }

            $lockedPayment->update([
                'status' => PaymentStatus::Reversed,

                'reversed_at' => now(),

                'reversal_reason' => trim($reason),

                'reversed_by' => $reversedBy->getKey(),
            ]);

            $chargeIds = $lockedPayment->allocations()->pluck('contribution_charge_id');
            $charges = ContributionCharge::query()
                ->whereKey($chargeIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($charges as $charge) {
                $this->chargeStatusSync->handle($charge);
            }

            $this->audit->log(AuditAction::PaymentReversed, $lockedPayment, $reversedBy, [
                'payment_id' => $lockedPayment->getKey(),
                'member_id' => $lockedPayment->member_id,
                'amount' => $lockedPayment->amount,
                'method' => $lockedPayment->method->value,
            ]);
        });

        $payment->refresh();
    }
}
