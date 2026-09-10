<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberPersonalDataChanged extends ShouldBeStored
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $birthDate,
    ) {}
}
