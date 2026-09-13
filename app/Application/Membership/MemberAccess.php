<?php

namespace App\Application\Membership;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Exceptions\MemberNotInCurrentClub;
use App\Domain\Membership\Models\Member;

final readonly class MemberAccess
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function ensureInCurrentClub(
        string $memberId,
    ): void {
        $exists = Member::query()
            ->whereKey($memberId)
            ->where(
                'club_id',
                $this->currentClub->id(),
            )
            ->exists();

        if (! $exists) {
            throw MemberNotInCurrentClub::forMember(
                $memberId
            );
        }
    }
}
