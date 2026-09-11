<?php

namespace App\Application\Membership\Handlers;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\LeaveMemberDepartment;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Models\Department;

final readonly class LeaveMemberDepartmentHandler
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function handle(
        LeaveMemberDepartment $command,
    ): void {
        Department::query()
            ->where(
                'club_id',
                $this->currentClub->id()
            )
            ->findOrFail(
                $command->departmentId
            );

        MemberAggregate::retrieve(
            $command->memberId
        )
            ->leaveDepartment(
                departmentId: $command->departmentId,

                leftAt: $command->leftAt,
            )
            ->persist();
    }
}
