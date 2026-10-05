<?php

namespace App\Application\Sepa;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Enums\SepaMandateStatus;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Domain\Sepa\Models\SepaMandate;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class PrepareSepaDebitRun
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function handle(
        string $name,
        CarbonImmutable $collectionDate,
        User $createdBy,
    ): SepaDebitRun {
        $club =
            $this->currentClub->get();

        $configuration =
            $club->sepaConfiguration;

        if (
            $configuration === null
            || ! $configuration->is_active
        ) {
            throw new DomainException(
                'Für diesen Verein ist keine aktive SEPA-Konfiguration vorhanden.'
            );
        }

        $earliestDate =
            CarbonImmutable::today()
                ->addDays(
                    $configuration
                        ->default_lead_days
                );

        if (
            $collectionDate->lt(
                $earliestDate
            )
        ) {
            throw new DomainException(
                'Das Einzugsdatum liegt vor dem konfigurierten Mindestvorlauf.'
            );
        }

        $run =
            SepaDebitRun::query()
                ->create([
                    'club_id' => $club->getKey(),

                    'status' => SepaDebitRunStatus::Draft,

                    'name' => trim($name),

                    'collection_date' => $collectionDate,

                    'created_by' => $createdBy->getKey(),
                ]);

        return $this->prepare(
            $run
        );
    }

    private function prepare(
        SepaDebitRun $run,
    ): SepaDebitRun {
        $itemsCount = 0;
        $errorsCount = 0;
        $totalAmount = '0.00';

        ContributionCharge::query()
            ->where(
                'club_id',
                $run->club_id
            )
            ->where(
                'status',
                ContributionChargeStatus::Open->value
            )
            ->where(
                'amount',
                '>',
                0
            )
            ->whereDate(
                'due_date',
                '<=',
                $run->collection_date
            )
            ->with([
                'member.activeSepaMandate',
            ])
            ->orderBy('id')
            ->chunkById(
                100,
                function ($charges) use (
                    $run,
                    &$itemsCount,
                    &$errorsCount,
                    &$totalAmount,
                ): void {
                    foreach ($charges as $charge) {
                        try {
                            $item =
                                $this->prepareCharge(
                                    run: $run,
                                    charge: $charge,
                                );

                            if ($item === null) {
                                continue;
                            }

                            $itemsCount++;

                            $totalAmount =
                                bcadd(
                                    $totalAmount,
                                    $item->amount,
                                    2
                                );
                        } catch (\Throwable $exception) {
                            $errorsCount++;

                            $run->errors()->create([
                                'member_id' => $charge->member_id,
                                'contribution_charge_id' => $charge->getKey(),
                                'message' => mb_substr(
                                    $exception instanceof DomainException
                                        ? $exception->getMessage()
                                        : 'Die Forderung konnte nicht für den SEPA-Lauf vorbereitet werden.',
                                    0,
                                    1000,
                                ),
                            ]);

                            report($exception);
                        }
                    }
                }
            );

        $run->update([
            'status' => SepaDebitRunStatus::Prepared,

            'items_count' => $itemsCount,

            'errors_count' => $errorsCount,

            'total_amount' => $totalAmount,

            'prepared_at' => now(),
        ]);

        return $run->refresh();
    }

    private function prepareCharge(
        SepaDebitRun $run,
        ContributionCharge $charge,
    ): ?SepaDebitItem {
        if (
            (string) $charge->club_id
            !== (string) $run->club_id
        ) {
            throw new DomainException(
                'Die Forderung gehört nicht zum aktuellen SEPA-Lauf.'
            );
        }

        $alreadyUsed = SepaDebitItem::query()
            ->where('contribution_charge_id', $charge->getKey())
            ->where('status', SepaDebitItemStatus::Prepared->value)
            ->exists();

        if ($alreadyUsed) {
            return null;
        }

        $member =
            $charge->member;

        $mandate =
            $member->activeSepaMandate;

        if ($mandate === null) {
            throw new DomainException(
                'Kein aktives SEPA-Mandat.'
            );
        }

        if ((string) $mandate->club_id !== (string) $run->club_id) {
            throw new DomainException(
                'Das SEPA-Mandat gehört nicht zum aktuellen Verein.'
            );
        }

        if (
            $mandate->status
            !== SepaMandateStatus::Active
        ) {
            throw new DomainException(
                'Das SEPA-Mandat ist nicht aktiv.'
            );
        }

        if (
            $mandate->valid_from !== null
            && $mandate->valid_from->gt(
                $run->collection_date
            )
        ) {
            throw new DomainException(
                'SEPA-Mandat am Einzugstag noch nicht gültig.'
            );
        }

        return $this->createItem(
            run: $run,
            charge: $charge,
            mandate: $mandate,
        );
    }

    private function createItem(
        SepaDebitRun $run,
        ContributionCharge $charge,
        SepaMandate $mandate,
    ): SepaDebitItem {
        $purpose =
            $this->buildPurpose(
                $charge
            );

        return DB::transaction(
            function () use (
                $run,
                $charge,
                $mandate,
                $purpose,
            ): SepaDebitItem {
                return SepaDebitItem::query()
                    ->create([
                        'club_id' => $run->club_id,

                        'sepa_debit_run_id' => $run->getKey(),

                        'contribution_charge_id' => $charge->getKey(),

                        'member_id' => $charge->member_id,

                        'sepa_mandate_id' => $mandate->getKey(),

                        'amount' => $charge->amount,

                        'purpose' => $purpose,

                        'account_holder' => $mandate
                            ->account_holder,

                        'iban' => $mandate->iban,

                        'bic' => $mandate->bic,

                        'mandate_reference' => $mandate
                            ->mandate_reference,

                        'mandate_signed_at' => $mandate
                            ->signed_at,
                    ]);
            }
        );
    }

    private function buildPurpose(
        ContributionCharge $charge,
    ): string {
        $purpose =
            trim(
                $charge->description
            );

        if ($purpose === '') {
            $purpose =
                'Mitgliedsbeitrag';
        }

        return mb_substr(
            $purpose,
            0,
            140
        );
    }
}
