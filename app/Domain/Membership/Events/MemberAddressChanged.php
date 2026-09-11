<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberAddressChanged extends ShouldBeStored
{
    public function __construct(
        public readonly string $street,
        public readonly string $houseNumber,
        public readonly string $postalCode,
        public readonly string $city,
        public readonly string $countryCode,
    ) {}
}
