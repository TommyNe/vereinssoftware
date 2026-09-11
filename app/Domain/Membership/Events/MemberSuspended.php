<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberSuspended extends ShouldBeStored
{
    public function __construct(
        public readonly string $suspendedAt,
        public readonly ?string $reason = null,
    ) {}
}
