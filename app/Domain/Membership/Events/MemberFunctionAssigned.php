<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberFunctionAssigned extends ShouldBeStored
{
    public function __construct(
        public readonly string $clubFunctionId,
        public readonly string $validFrom,
    ) {
    }
}
