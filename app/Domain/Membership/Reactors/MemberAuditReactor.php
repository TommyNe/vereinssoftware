<?php

namespace App\Domain\Membership\Reactors;

use App\Application\Audit\AuditLogger;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Membership\Events\MemberAddressChanged;
use App\Domain\Membership\Events\MemberContactDataChanged;
use App\Domain\Membership\Events\MemberLeftClub;
use App\Domain\Membership\Events\MemberPersonalDataChanged;
use App\Domain\Membership\Events\MemberReactivated;
use App\Domain\Membership\Events\MembershipTypeChanged;
use App\Domain\Membership\Events\MemberSuspended;
use App\Domain\Membership\Models\Member;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;
use Spatie\EventSourcing\Facades\Projectionist;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final  class MemberAuditReactor extends Reactor
{
    public function __construct(
        private AuditLogger $audit,
    ) {
    }

    public function onMemberAddressChanged(
        MemberAddressChanged $event,
    ): void {
        if (Projectionist::isReplaying()) {
            return;
        }
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $this->audit->log(
            AuditAction::MemberAddressChanged,
            $member,
        );
    }

    public function onMemberContactDataChanged(
        MemberContactDataChanged $event,
    ): void {
        if (Projectionist::isReplaying()) {
            return;
        }
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $this->audit->log(
            AuditAction::MemberContactDataChanged,
            $member,
        );
    }

    public function onMemberPersonalDataChanged(
        MemberPersonalDataChanged $event,
    ): void {
        if (Projectionist::isReplaying()) {
            return;
        }
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $this->audit->log(
            AuditAction::MemberPersonalDataChanged,
            $member,
        );
    }

    public function onMembershipTypeChanged(
        MembershipTypeChanged $event,
    ): void {
        if (Projectionist::isReplaying()) {
            return;
        }

        $this->log(
            $event,
            AuditAction::MembershipTypeChanged,
        );
    }

    public function onMemberSuspended(
        MemberSuspended $event,
    ): void {
        if (Projectionist::isReplaying()) {
            return;
        }

        $this->log(
            $event,
            AuditAction::MemberSuspended,
        );
    }

    public function onMemberReactivated(
        MemberReactivated $event,
    ): void {
        if (Projectionist::isReplaying()) {
            return;
        }

        $this->log(
            $event,
            AuditAction::MemberReactivated,
        );
    }

    public function onMemberLeftClub(
        MemberLeftClub $event,
    ): void {
        if (Projectionist::isReplaying()) {
            return;
        }

        $this->log(
            $event,
            AuditAction::MemberLeftClub,
        );
    }

    private function log(
        ShouldBeStored $event,
        AuditAction $action,
    ): void {
        if (Projectionist::isReplaying()) {
            return;
        }

        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $this->audit->log(
            $action,
            $member,
        );
    }

    private function shouldSkipAudit(): bool
    {
        return Projectionist::isReplaying();
    }
}
