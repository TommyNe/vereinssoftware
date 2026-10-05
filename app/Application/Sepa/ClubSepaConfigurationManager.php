<?php

namespace App\Application\Sepa;

use App\Application\Club\CurrentClub;
use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Domain\Sepa\ValueObjects\CreditorIdentifier;
use App\Domain\Sepa\ValueObjects\Iban;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class ClubSepaConfigurationManager
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {
    }

    public function save(
        string $creditorIdentifier,
        string $accountHolder,
        string $iban,
        ?string $bic,
        ?string $mandateReferencePrefix,
        int $defaultLeadDays,
        ?string $defaultPurpose,
        bool $isActive,
        User $user,
    ): ClubSepaConfiguration {
        $clubId =
            $this->currentClub->id();

        $creditorIdentifier =
            (string) new CreditorIdentifier(
                $creditorIdentifier
            );

        $iban =
            (string) new Iban(
                $iban
            );

        $accountHolder =
            trim(
                $accountHolder
            );

        if ($accountHolder === '') {
            throw new DomainException(
                'Der Kontoinhaber darf nicht leer sein.'
            );
        }

        if (
            $defaultLeadDays < 0
            || $defaultLeadDays > 30
        ) {
            throw new DomainException(
                'Die Vorlauftage müssen zwischen 0 und 30 liegen.'
            );
        }

        $bic =
            $this->normalizeBic(
                $bic
            );

        $mandateReferencePrefix =
            $this->normalizePrefix(
                $mandateReferencePrefix
            );

        $defaultPurpose =
            $defaultPurpose !== null
                ? trim($defaultPurpose)
                : null;

        return DB::transaction(
            function () use (
                $clubId,
                $creditorIdentifier,
                $accountHolder,
                $iban,
                $bic,
                $mandateReferencePrefix,
                $defaultLeadDays,
                $defaultPurpose,
                $isActive,
                $user,
            ): ClubSepaConfiguration {
                $existing =
                    ClubSepaConfiguration::query()
                        ->where(
                            'club_id',
                            $clubId
                        )
                        ->first();

                if ($existing === null) {
                    return ClubSepaConfiguration::query()
                        ->create([
                            'club_id' =>
                                $clubId,

                            'creditor_identifier' =>
                                $creditorIdentifier,

                            'account_holder' =>
                                $accountHolder,

                            'iban' =>
                                $iban,

                            'bic' =>
                                $bic,

                            'mandate_reference_prefix' =>
                                $mandateReferencePrefix,

                            'default_lead_days' =>
                                $defaultLeadDays,

                            'default_purpose' =>
                                $defaultPurpose,

                            'is_active' =>
                                $isActive,

                            'created_by' =>
                                $user->getKey(),

                            'updated_by' =>
                                $user->getKey(),
                        ]);
                }

                $existing->update([
                    'creditor_identifier' =>
                        $creditorIdentifier,

                    'account_holder' =>
                        $accountHolder,

                    'iban' =>
                        $iban,

                    'bic' =>
                        $bic,

                    'mandate_reference_prefix' =>
                        $mandateReferencePrefix,

                    'default_lead_days' =>
                        $defaultLeadDays,

                    'default_purpose' =>
                        $defaultPurpose,

                    'is_active' =>
                        $isActive,

                    'updated_by' =>
                        $user->getKey(),
                ]);

                return $existing->refresh();
            }
        );
    }

    private function normalizeBic(
        ?string $bic,
    ): ?string {
        if ($bic === null) {
            return null;
        }

        $bic =
            strtoupper(
                preg_replace(
                    '/\s+/',
                    '',
                    trim($bic)
                ) ?? ''
            );

        if ($bic === '') {
            return null;
        }

        /*
         * BIC ist üblicherweise
         * 8 oder 11 Zeichen.
         */
        if (
            !preg_match(
                '/^[A-Z0-9]{8}([A-Z0-9]{3})?$/',
                $bic
            )
        ) {
            throw new DomainException(
                'Die BIC hat ein ungültiges Format.'
            );
        }

        return $bic;
    }

    private function normalizePrefix(
        ?string $prefix,
    ): ?string {
        if ($prefix === null) {
            return null;
        }

        $prefix =
            strtoupper(
                trim($prefix)
            );

        if ($prefix === '') {
            return null;
        }

        if (
            !preg_match(
                '/^[A-Z0-9._-]+$/',
                $prefix
            )
        ) {
            throw new DomainException(
                'Das Mandatspräfix enthält ungültige Zeichen.'
            );
        }

        if (
            mb_strlen(
                $prefix
            ) > 30
        ) {
            throw new DomainException(
                'Das Mandatspräfix ist zu lang.'
            );
        }

        return $prefix;
    }
}
