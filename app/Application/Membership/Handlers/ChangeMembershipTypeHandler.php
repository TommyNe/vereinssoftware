<?php

namespace App\Application\Membership\Handlers;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\ChangeMembershipType;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Models\MembershipType;

final readonly class ChangeMembershipTypeHandler
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function handle(
        ChangeMembershipType $command,
    ): void {
        MembershipType::query()
            ->where(
                'club_id',
                $this->currentClub->id()
            )
            ->where('is_active', true)
            ->findOrFail(
                $command->membershipTypeId
            );

        MemberAggregate::retrieve(
            $command->memberId
        )
            ->changeMembershipType(
                $command->membershipTypeId
            )
            ->persist();
    }
}
