<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberFunctionEnded extends ShouldBeStored
{
    public function __construct(
        public readonly string $clubFunctionId,
        public readonly string $validUntil,
    ) {}
}
