<?php

namespace App\Domain\Club\Enums;

enum ClubInvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Offen',

            self::Accepted => 'Akzeptiert',

            self::Revoked => 'Widerrufen',

            self::Expired => 'Abgelaufen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',

            self::Accepted => 'success',

            self::Revoked => 'danger',

            self::Expired => 'gray',
        };
    }
}
