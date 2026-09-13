<?php

namespace App\Application\Membership\Exceptions;

use RuntimeException;

final class MemberNotInCurrentClub extends RuntimeException
{
    public static function forMember(
        string $memberId,
    ): self {
        return new self(
            sprintf(
                'Mitglied [%s] gehört nicht zum aktuellen Verein.',
                $memberId,
            )
        );
    }
}
