<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberReactivated extends ShouldBeStored
{
    public function __construct(
        public readonly string $reactivatedAt,
    ) {}
}
