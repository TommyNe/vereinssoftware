<?php

namespace App\Application\Membership\Handlers;

use App\Application\Membership\Commands\SuspendMember;
use App\Application\Membership\MemberAccess;
use App\Domain\Membership\Aggregates\MemberAggregate;

final readonly class SuspendMemberHandler
{
    public function __construct(
        private MemberAccess $memberAccess,
    ) {}

    public function handle(
        SuspendMember $command,
    ): void {
        $this->memberAccess->ensureInCurrentClub(
            $command->memberId,
        );

        MemberAggregate::retrieve(
            $command->memberId,
        )
            ->suspend(
                suspendedAt: $command->suspendedAt,
                reason: $command->reason,
            )
            ->persist();
    }
}
