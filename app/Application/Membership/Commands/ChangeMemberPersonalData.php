<?php

namespace App\Application\Membership\Commands;

final readonly class ChangeMemberPersonalData
{
    public function __construct(
        public string $memberId,
        public string $firstName,
        public string $lastName,
        public ?string $birthDate,
    ) {}
}
