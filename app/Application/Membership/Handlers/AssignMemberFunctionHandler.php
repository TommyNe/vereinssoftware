<?php

namespace App\Application\Membership\Handlers;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\AssignMemberFunction;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Models\ClubFunction;

final readonly class AssignMemberFunctionHandler
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function handle(
        AssignMemberFunction $command,
    ): void {
        ClubFunction::query()
            ->where(
                'club_id',
                $this->currentClub->id()
            )
            ->where(
                'is_active',
                true
            )
            ->findOrFail(
                $command->clubFunctionId
            );

        MemberAggregate::retrieve(
            $command->memberId
        )
            ->assignFunction(
                clubFunctionId: $command->clubFunctionId,

                validFrom: $command->validFrom,
            )
            ->persist();
    }
}
