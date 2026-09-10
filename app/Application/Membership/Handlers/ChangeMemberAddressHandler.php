<?php

namespace App\Application\Membership\Handlers;

use App\Application\Membership\Commands\ChangeMemberAddress;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\ValueObjects\Address;

final readonly class ChangeMemberAddressHandler
{
    public function handle(
        ChangeMemberAddress $command,
    ): void {
        MemberAggregate::retrieve(
            $command->memberId
        )
            ->changeAddress(
                new Address(
                    street: $command->street,
                    houseNumber: $command->houseNumber,
                    postalCode: $command->postalCode,
                    city: $command->city,
                    countryCode: $command->countryCode,
                )
            )
            ->persist();
    }
}
