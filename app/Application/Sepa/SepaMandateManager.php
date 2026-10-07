<?php

namespace App\Application\Sepa;

use App\Application\Club\CurrentClub;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaMandateStatus;
use App\Domain\Sepa\Models\SepaMandate;
use App\Domain\Sepa\ValueObjects\Iban;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class SepaMandateManager
{
    public function __construct(
        private CurrentClub $currentClub,
        private MandateReferenceGenerator $referenceGenerator,
    ) {}

    /**
     * @throws \Throwable
     */
    public function create(
        Member $member,
        string $mandateReference,
        string $accountHolder,
        string $iban,
        ?string $bic,
        CarbonImmutable $signedAt,
        ?CarbonImmutable $validFrom,
        User $createdBy,
    ): SepaMandate {
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

        $mandateReference = trim($mandateReference);

        if ($mandateReference === '') {
            $mandateReference = $this->referenceGenerator->generate($member);
        }

        if ($mandateReference === '') {
            throw new DomainException(
                'Die Mandatsreferenz darf nicht leer sein.'
            );
        }

        $iban =
            new Iban(
                $iban
            );

        return DB::transaction(
            function () use (
                $clubId,
                $member,
                $mandateReference,
                $accountHolder,
                $iban,
                $bic,
                $signedAt,
                $validFrom,
                $createdBy,
            ): SepaMandate {
                $hasActiveMandate =
                    SepaMandate::query()
                        ->where(
                            'club_id',
                            $clubId
                        )
                        ->where(
                            'member_id',
                            $member->getKey()
                        )
                        ->where(
                            'status',
                            SepaMandateStatus::Active->value
                        )
                        ->exists();

                if ($hasActiveMandate) {
                    throw new DomainException(
                        'Für dieses Mitglied existiert bereits ein aktives SEPA-Mandat.'
                    );
                }

                return SepaMandate::query()
                    ->create([
                        'club_id' => $clubId,

                        'member_id' => $member->getKey(),

                        'status' => SepaMandateStatus::Active,

                        'mandate_reference' => $mandateReference,

                        'account_holder' => trim(
                            $accountHolder
                        ),

                        'iban' => (string) $iban,

                        'bic' => $bic !== null
                                ? strtoupper(
                                    str_replace(
                                        ' ',
                                        '',
                                        $bic
                                    )
                                )
                                : null,

                        'signed_at' => $signedAt,

                        'valid_from' => $validFrom
                            ?? $signedAt,

                        'created_by' => $createdBy->getKey(),
                    ]);
            }
        );
    }

    public function revoke(
        SepaMandate $mandate,
        string $reason,
        User $revokedBy,
    ): void {
        if (
            (string) $mandate->club_id
            !== $this->currentClub->id()
        ) {
            throw new DomainException(
                'Das SEPA-Mandat gehört nicht zum aktuellen Verein.'
            );
        }

        if (
            $mandate->status
            !== SepaMandateStatus::Active
        ) {
            throw new DomainException(
                'Nur aktive SEPA-Mandate können widerrufen werden.'
            );
        }

        if (
            trim($reason) === ''
        ) {
            throw new DomainException(
                'Eine Begründung ist erforderlich.'
            );
        }

        $mandate->update([
            'status' => SepaMandateStatus::Revoked,

            'revoked_at' => now(),

            'revocation_reason' => trim($reason),

            'revoked_by' => $revokedBy->getKey(),
        ]);
    }
}
