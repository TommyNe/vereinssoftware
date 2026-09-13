<?php

namespace App\Application\Membership\Handlers;

use App\Application\Membership\Commands\ChangeMemberPersonalData;
use App\Application\Membership\MemberAccess;
use App\Domain\Membership\Aggregates\MemberAggregate;

final readonly class ChangeMemberPersonalDataHandler
{
    public function __construct(
        private MemberAccess $memberAccess,
    ) {}

    public function handle(
        ChangeMemberPersonalData $command,
    ): void {
        $this->memberAccess
            ->ensureInCurrentClub(
                $command->memberId
            );

        MemberAggregate::retrieve(
            $command->memberId
        )
            ->changePersonalData(
                firstName: $command->firstName,

                lastName: $command->lastName,

                birthDate: $command->birthDate,
            )
            ->persist();
    }
}
