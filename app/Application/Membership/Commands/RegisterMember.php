<?php

namespace App\Application\Membership\Commands;

use Carbon\CarbonImmutable;

final readonly class RegisterMember
{
    public function __construct(
        public string $memberId,
        public string $clubId,
        public string $memberNumber,
        public string $firstName,
        public string $lastName,
        public ?CarbonImmutable $birthDate,
        public CarbonImmutable $joinedAt,
    ) {}
}
