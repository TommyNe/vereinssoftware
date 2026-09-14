<?php

namespace App\Application\Membership\Handlers;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\JoinMemberDepartment;
use App\Application\Membership\MemberAccess;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Models\Department;

final readonly class JoinMemberDepartmentHandler
{
    public function __construct(
        private CurrentClub $currentClub,
        private MemberAccess $memberAccess,
    ) {}

    public function handle(
        JoinMemberDepartment $command,
    ): void {
        $this->memberAccess->ensureInCurrentClub(
            $command->memberId
        );

        $department = Department::query()
            ->whereKey(
                $command->departmentId
            )
            ->where(
                'club_id',
                $this->currentClub->id()
            )
            ->where(
                'is_active',
                true
            )
            ->firstOrFail();

        MemberAggregate::retrieve(
            $command->memberId
        )
            ->joinDepartment(
                departmentId: (string) $department->getKey(),

                joinedAt: $command->joinedAt,
            )
            ->persist();
    }
}
