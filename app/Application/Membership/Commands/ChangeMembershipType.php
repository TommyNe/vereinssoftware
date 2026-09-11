<?php

namespace App\Application\Membership\Commands;

final readonly class ChangeMembershipType
{
    public function __construct(
        public string $memberId,
        public string $membershipTypeId,
    ) {}
}
