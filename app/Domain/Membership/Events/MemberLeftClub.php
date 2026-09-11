<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberLeftClub extends ShouldBeStored
{
    public function __construct(
        public readonly string $leftAt,
        public readonly ?string $reason = null,
    ) {}
}
