<?php

namespace App\Application\Sepa;

use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Enums\SepaMandateStatus;
use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Domain\Sepa\ValueObjects\Iban;
use DomainException;

final class ValidateSepaDebitRunForExport
{
    public function validate(
        SepaDebitRun $run,
        ClubSepaConfiguration $configuration,
    ): void {
        if ($run->status !== SepaDebitRunStatus::Prepared) {
            throw new DomainException(
                'Nur vorbereitete Lastschriftläufe können exportiert werden.'
            );
        }

        if (
            (string) $configuration->club_id
            !== (string) $run->club_id
            || ! $configuration->is_active
        ) {
            throw new DomainException(
                'Keine gültige SEPA-Konfiguration vorhanden.'
            );
        }

        new Iban($configuration->iban);

        $this->requireBic($configuration->bic);

        $items = $run->items()
            ->with(['charge', 'mandate'])
            ->get();

        if ($items->isEmpty()) {
            throw new DomainException(
                'Der SEPA-Lauf enthält keine Positionen.'
            );
        }

        if ($run->errors_count > 0) {
            throw new DomainException(
                'Der SEPA-Lauf enthält ungeklärte Vorbereitungsfehler.'
            );
        }

        $total = '0.00';

        foreach ($items as $item) {
            if (
                (string) $item->club_id
                !== (string) $run->club_id
            ) {
                throw new DomainException(
                    'Eine Lastschriftposition gehört zu einem anderen Verein.'
                );
            }

            if (
                $item->status->value !== 'prepared'
            ) {
                throw new DomainException(
                    'Der Lauf enthält eine nicht aktive Lastschriftposition.'
                );
            }

            $charge = $item->charge;

            if (
                $charge === null
                || (string) $charge->club_id !== (string) $run->club_id
                || (string) $charge->member_id !== (string) $item->member_id
                || $charge->status !== ContributionChargeStatus::Open
            ) {
                throw new DomainException(
                    'Mindestens eine Forderung ist nicht mehr offen oder falsch zugeordnet.'
                );
            }

            $mandate = $item->mandate;

            if (
                $mandate === null
                || (string) $mandate->club_id !== (string) $run->club_id
                || (string) $mandate->member_id !== (string) $item->member_id
                || $mandate->status !== SepaMandateStatus::Active
                || (
                    $mandate->valid_from !== null
                    && $mandate->valid_from->gt($run->collection_date)
                )
            ) {
                throw new DomainException(
                    'Mindestens ein SEPA-Mandat ist nicht mehr gültig.'
                );
            }

            new Iban($item->iban);

            $this->requireBic($item->bic);

            if (
                bccomp((string) $item->amount, '0.00', 2) <= 0
                || trim($item->account_holder) === ''
                || trim($item->mandate_reference) === ''
                || trim($item->purpose) === ''
                || $item->mandate_signed_at->gt($run->collection_date)
            ) {
                throw new DomainException(
                    'Eine Lastschriftposition enthält unvollständige Angaben.'
                );
            }

            $total = bcadd(
                $total,
                (string) $item->amount,
                2,
            );
        }

        if (
            $items->count() !== (int) $run->items_count
            || bccomp($total, (string) $run->total_amount, 2) !== 0
        ) {
            throw new DomainException(
                'Anzahl oder Gesamtsumme des Lastschriftlaufs stimmt nicht.'
            );
        }
    }

    private function requireBic(?string $bic): void
    {
        if (
            $bic === null
            || ! preg_match(
                '/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/',
                $bic,
            )
        ) {
            throw new DomainException(
                'Für den Export fehlt eine gültig formatierte BIC.'
            );
        }
    }
}
