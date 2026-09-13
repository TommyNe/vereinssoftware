<?php

namespace App\Application\Membership\Handlers;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\RegisterMember;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\ValueObjects\MemberNumber;

final readonly class RegisterMemberHandler
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {
    }
    public function handle(RegisterMember $command): void
    {
        MemberAggregate::retrieve($command->memberId)
            ->register(
                clubId: $this->currentClub->id(),
                memberNumber: new MemberNumber(
                    $command->memberNumber
                ),
                firstName: $command->firstName,
                lastName: $command->lastName,
                birthDate: $command->birthDate,
                joinedAt: $command->joinedAt,
            )
            ->persist();
    }
}
