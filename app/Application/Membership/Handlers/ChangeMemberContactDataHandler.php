<?php

namespace App\Application\Membership\Handlers;

use App\Application\Membership\Commands\ChangeMemberContactData;
use App\Domain\Membership\Aggregates\MemberAggregate;

final class ChangeMemberContactDataHandler
{
    public function handle(
        ChangeMemberContactData $command,
    ): void {
        MemberAggregate::retrieve(
            $command->memberId
        )
            ->changeContactData(
                email: $command->email,
                phone: $command->phone,
                mobile: $command->mobile,
            )
            ->persist();
    }
}
