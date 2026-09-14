<?php

namespace App\Application\Membership\Handlers;

use App\Application\Membership\Commands\ReactivateMember;
use App\Application\Membership\MemberAccess;
use App\Domain\Membership\Aggregates\MemberAggregate;

final readonly class ReactivateMemberHandler
{
    public function __construct(
        private MemberAccess $memberAccess,
    ) {}

    public function handle(
        ReactivateMember $command,
    ): void {
        $this->memberAccess->ensureInCurrentClub(
            $command->memberId,
        );

        MemberAggregate::retrieve(
            $command->memberId,
        )
            ->reactivate(
                reactivatedAt: $command->reactivatedAt,
            )
            ->persist();
    }
}
