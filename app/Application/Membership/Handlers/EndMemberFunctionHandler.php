<?php

namespace App\Application\Membership\Handlers;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\EndMemberFunction;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Models\ClubFunction;

final readonly class EndMemberFunctionHandler
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function handle(
        EndMemberFunction $command,
    ): void {
        ClubFunction::query()
            ->where(
                'club_id',
                $this->currentClub->id()
            )
            ->findOrFail(
                $command->clubFunctionId
            );

        MemberAggregate::retrieve(
            $command->memberId
        )
            ->endFunction(
                clubFunctionId: $command->clubFunctionId,

                validUntil: $command->validUntil,
            )
            ->persist();
    }
}
