<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberContactDataChanged extends ShouldBeStored
{
    public function __construct(
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $mobile,
    ) {}
}
