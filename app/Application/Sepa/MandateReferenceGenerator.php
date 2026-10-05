<?php

namespace App\Application\Sepa;

use App\Application\Club\CurrentClub;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Models\SepaMandate;
use DomainException;

final readonly class MandateReferenceGenerator
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {
    }

    public function generate(
        Member $member,
    ): string {
        $club =
            $this->currentClub
                ->get();

        if (
            (string) $member->club_id
            !== (string) $club->getKey()
        ) {
            throw new DomainException(
                'Das Mitglied gehört nicht zum aktuellen Verein.'
            );
        }

        $configuration =
            $club->sepaConfiguration;

        if ($configuration === null) {
            throw new DomainException(
                'Für den Verein ist keine SEPA-Konfiguration vorhanden.'
            );
        }

        $prefix =
            $configuration
                ->mandate_reference_prefix
            ?? 'M-';

        /*
         * Mitgliedsnummer als gut lesbare
         * Referenzbasis.
         */
        $base =
            $prefix
            .$member->member_number;

        if (
            ! SepaMandate::query()
                ->where(
                    'club_id',
                    $club->getKey()
                )
                ->where(
                    'mandate_reference',
                    $base
                )
                ->exists()
        ) {
            return $base;
        }

        /*
         * Falls schon vergeben:
         * Suffix ergänzen.
         */
        for (
            $counter = 2;
            $counter <= 999;
            ++$counter
        ) {
            $candidate =
                $base
                .'-'
                .$counter;

            $exists =
                SepaMandate::query()
                    ->where(
                        'club_id',
                        $club->getKey()
                    )
                    ->where(
                        'mandate_reference',
                        $candidate
                    )
                    ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        throw new DomainException(
            'Es konnte keine eindeutige Mandatsreferenz erzeugt werden.'
        );
    }
}
