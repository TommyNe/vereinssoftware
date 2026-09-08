<?php

namespace App\Domain\Membership\Projectors;

use App\Domain\Membership\Events\MemberRegistered;
use App\Domain\Membership\Models\Member;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\StoredEvents\StoredEvent;

final class MemberProjector extends Projector
{
    public function onMemberRegistered(
        MemberRegistered $event,
        StoredEvent $storedEvent,
    ): void {
        $member = new Member([
            'id' => $storedEvent->aggregate_uuid,
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

    public function resetState(): void
    {
        Member::query()->delete();
    }
}
