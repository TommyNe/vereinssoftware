<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberRegistered extends ShouldBeStored
{
    public function __construct(
        public readonly string $clubId,
        public readonly string $memberNumber,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $birthDate,
        public readonly string $joinedAt,
    ) {}
}
