<?php

namespace App\Domain\Membership\Projectors;

use App\Domain\Membership\Events\MemberAddressChanged;
use App\Domain\Membership\Events\MemberRegistered;
use App\Domain\Membership\Models\Member;
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

    public function resetState(): void
    {
        Member::query()
            ->each(
                fn (Member $member) => $member->writeable()->delete()
            );
    }
}
