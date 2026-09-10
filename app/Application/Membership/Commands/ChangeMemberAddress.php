<?php

namespace App\Application\Membership\Commands;

final readonly class ChangeMemberAddress
{
    public function __construct(
        public string $memberId,
        public string $street,
        public string $houseNumber,
        public string $postalCode,
        public string $city,
        public string $countryCode,
    ) {}
}
