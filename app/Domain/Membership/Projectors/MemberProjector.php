<?php

namespace App\Domain\Membership\Projectors;

use App\Domain\Membership\Enums\MembershipStatus;
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
use App\Domain\Membership\Models\MemberDepartment;
use App\Domain\Membership\Models\MemberFunction;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

final class MemberProjector extends Projector
{
    public function onMemberRegistered(
        MemberRegistered $event,
    ): void {
        $member = new Member([
            'uuid' => $event->aggregateRootUuid(),
            'club_id' => $event->clubId,
            'member_number' => $event->memberNumber,
            'first_name' => $event->firstName,
            'last_name' => $event->lastName,
            'birth_date' => $event->birthDate,
            'joined_at' => $event->joinedAt,
            'status' => 'active',
        ]);

        $member->writeable()->save();
    }

    public function onMemberAddressChanged(
        MemberAddressChanged $event,
    ): void {
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $member
            ->writeable()
            ->update([
                'street' => $event->street,
                'house_number' => $event->houseNumber,
                'postal_code' => $event->postalCode,
                'city' => $event->city,
                'country_code' => $event->countryCode,
            ]);
    }

    public function onMemberContactDataChanged(
        MemberContactDataChanged $event,
    ): void {
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $member
            ->writeable()
            ->update([
                'email' => $event->email,
                'phone' => $event->phone,
                'mobile' => $event->mobile,
            ]);
    }

    public function onMemberPersonalDataChanged(
        MemberPersonalDataChanged $event,
    ): void {
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $member
            ->writeable()
            ->update([
                'first_name' => $event->firstName,
                'last_name' => $event->lastName,
                'birth_date' => $event->birthDate,
            ]);
    }

    public function onMemberSuspended(
        MemberSuspended $event,
    ): void {
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $member
            ->writeable()
            ->update([
                'status' => MembershipStatus::Suspended->value,
            ]);
    }

    public function onMemberReactivated(
        MemberReactivated $event,
    ): void {
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $member
            ->writeable()
            ->update([
                'status' => MembershipStatus::Active->value,
                'left_at' => null,
            ]);
    }

    public function onMemberLeftClub(
        MemberLeftClub $event,
    ): void {
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $member
            ->writeable()
            ->update([
                'status' => MembershipStatus::Left->value,
                'left_at' => $event->leftAt,
            ]);
    }

    public function onMembershipTypeChanged(
        MembershipTypeChanged $event,
    ): void {
        $member = Member::query()
            ->findOrFail(
                $event->aggregateRootUuid()
            );

        $member
            ->writeable()
            ->update([
                'membership_type_id' => $event->membershipTypeId,
            ]);
    }

    public function onMemberJoinedDepartment(
        MemberJoinedDepartment $event,
    ): void {
        $membership = new MemberDepartment([
            'member_id' => $event->aggregateRootUuid(),

            'department_id' => $event->departmentId,

            'joined_at' => $event->joinedAt,
        ]);

        $membership
            ->writeable()
            ->save();
    }

    public function onMemberLeftDepartment(
        MemberLeftDepartment $event,
    ): void {
        $membership = MemberDepartment::query()
            ->where(
                'member_id',
                $event->aggregateRootUuid(),
            )
            ->where(
                'department_id',
                $event->departmentId,
            )
            ->firstOrFail();

        $membership
            ->writeable()
            ->delete();
    }

    public function onMemberFunctionAssigned(
        MemberFunctionAssigned $event,
    ): void {
        $assignment = new MemberFunction([
            'member_id' => $event->aggregateRootUuid(),

            'club_function_id' => $event->clubFunctionId,

            'valid_from' => $event->validFrom,

            'valid_until' => null,
        ]);

        $assignment
            ->writeable()
            ->save();
    }

    public function onMemberFunctionEnded(
        MemberFunctionEnded $event,
    ): void {
        $assignment = MemberFunction::query()
            ->where(
                'member_id',
                $event->aggregateRootUuid()
            )
            ->where(
                'club_function_id',
                $event->clubFunctionId
            )
            ->whereNull('valid_until')
            ->firstOrFail();

        $assignment
            ->writeable()
            ->update([
                'valid_until' => $event->validUntil,
            ]);
    }

    public function resetState(): void
    {
        MemberFunction::query()
            ->each(
                fn (MemberFunction $assignment) => $assignment
                    ->writeable()
                    ->delete()
            );

        MemberDepartment::query()
            ->each(
                fn (MemberDepartment $membership) => $membership
                    ->writeable()
                    ->delete()
            );

        Member::query()
            ->each(
                fn (Member $member) => $member
                    ->writeable()
                    ->delete()
            );
    }
}
