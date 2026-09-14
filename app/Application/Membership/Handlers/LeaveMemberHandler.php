<?php

namespace App\Application\Membership\Handlers;

use App\Application\Membership\Commands\LeaveMember;
use App\Application\Membership\MemberAccess;
use App\Domain\Membership\Aggregates\MemberAggregate;

final readonly class LeaveMemberHandler
{
    public function __construct(
        private MemberAccess $memberAccess,
    ) {}

    public function handle(
        LeaveMember $command,
    ): void {
        $this->memberAccess->ensureInCurrentClub(
            $command->memberId,
        );

        MemberAggregate::retrieve(
            $command->memberId,
        )
            ->leave(
                leftAt: $command->leftAt,
                reason: $command->reason,
            )
            ->persist();
    }
}
