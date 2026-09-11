<?php

namespace App\Application\Membership\Handlers;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\JoinMemberDepartment;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Models\Department;

final readonly class JoinMemberDepartmentHandler
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function handle(
        JoinMemberDepartment $command,
    ): void {
        Department::query()
            ->where(
                'club_id',
                $this->currentClub->id()
            )
            ->where('is_active', true)
            ->findOrFail(
                $command->departmentId
            );

        MemberAggregate::retrieve(
            $command->memberId
        )
            ->joinDepartment(
                departmentId: $command->departmentId,

                joinedAt: $command->joinedAt,
            )
            ->persist();
    }
}
