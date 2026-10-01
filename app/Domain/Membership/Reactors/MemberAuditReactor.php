<?php

namespace App\Domain\Membership\Reactors;

use App\Application\Audit\AuditLogger;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Membership\Events\MemberAddressChanged;
use App\Domain\Membership\Events\MemberContactDataChanged;
use App\Domain\Membership\Events\MemberFunctionAssigned;
use App\Domain\Membership\Events\MemberFunctionEnded;
use App\Domain\Membership\Events\MemberJoinedDepartment;
use App\Domain\Membership\Events\MemberLeftClub;
use App\Domain\Membership\Events\MemberLeftDepartment;
use App\Domain\Membership\Events\MemberPersonalDataChanged;
use App\Domain\Membership\Events\MemberReactivated;
use App\Domain\Membership\Events\MemberRegistered;
use App\Domain\Membership\Events\MembershipTypeChanged;
use App\Domain\Membership\Events\MemberSuspended;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberAuditReactor extends Reactor
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function onMemberAddressChanged(
        MemberAddressChanged $event,
    ): void {
        $this->log(
            $event,
            AuditAction::MemberAddressChanged,
        );
    }

    public function onMemberRegistered(
        MemberRegistered $event,
    ): void {
        $this->log(
            $event,
            AuditAction::MemberRegistered,
        );
    }

    public function onMemberContactDataChanged(
        MemberContactDataChanged $event,
    ): void {
        $this->log(
            $event,
            AuditAction::MemberContactDataChanged,
        );
    }

    public function onMemberPersonalDataChanged(
        MemberPersonalDataChanged $event,
    ): void {
        $this->log(
            $event,
            AuditAction::MemberPersonalDataChanged,
        );
    }

    public function onMembershipTypeChanged(
        MembershipTypeChanged $event,
    ): void {
        $this->log(
            $event,
            AuditAction::MembershipTypeChanged,
            [
                'membership_type_id' => $event->membershipTypeId,
            ],
        );
    }

    public function onMemberSuspended(
        MemberSuspended $event,
    ): void {
        $this->log(
            $event,
            AuditAction::MemberSuspended,
        );
    }

    public function onMemberReactivated(
        MemberReactivated $event,
    ): void {
        $this->log(
            $event,
            AuditAction::MemberReactivated,
        );
    }

    public function onMemberLeftClub(
        MemberLeftClub $event,
    ): void {
        $this->log(
            $event,
            AuditAction::MemberLeftClub,
        );
    }

    public function onMemberJoinedDepartment(
        MemberJoinedDepartment $event,
    ): void {
        $this->log(
            $event,
            AuditAction::DepartmentJoined,
            [
                'department_id' => $event->departmentId,
            ],
        );
    }

    public function onMemberLeftDepartment(
        MemberLeftDepartment $event,
    ): void {
        $this->log(
            $event,
            AuditAction::DepartmentLeft,
            [
                'department_id' => $event->departmentId,
            ],
        );
    }

    public function onMemberFunctionAssigned(
        MemberFunctionAssigned $event,
    ): void {
        $this->log(
            $event,
            AuditAction::FunctionAssigned,
            [
                'club_function_id' => $event->clubFunctionId,
            ],
        );
    }

    public function onMemberFunctionEnded(
        MemberFunctionEnded $event,
    ): void {
        $this->log(
            $event,
            AuditAction::FunctionEnded,
            [
                'club_function_id' => $event->clubFunctionId,
            ],
        );
    }

    private function log(
        ShouldBeStored $event,
        AuditAction $action,
        array $properties = [],
    ): void {
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $metadata = $event->metaData();
        $actorId = $metadata['user_id'] ?? null;

        $this->audit->log(
            action: $action,
            subject: $member,
            user: $actorId === null ? null : User::query()->find($actorId),
            properties: [
                'club_id' => $metadata['club_id'] ?? $member->club_id,
                'user_id' => $actorId,
                'ip_address' => $metadata['ip_address'] ?? null,
                'user_agent' => $metadata['user_agent'] ?? null,
                'method' => $metadata['method'] ?? null,
                'path' => $metadata['path'] ?? null,
                'source' => $metadata['source'] ?? null,
                'stored_event_id' => $event->storedEventId(),
                ...$properties,
            ],
            useRequestContext: false,
        );
    }
}
