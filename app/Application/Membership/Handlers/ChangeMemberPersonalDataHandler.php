<?php

namespace App\Application\Membership\Handlers;

use App\Application\Membership\Commands\ChangeMemberPersonalData;
use App\Domain\Membership\Aggregates\MemberAggregate;

final readonly class ChangeMemberPersonalDataHandler
{
    public function handle(
        ChangeMemberPersonalData $command,
    ): void {
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
