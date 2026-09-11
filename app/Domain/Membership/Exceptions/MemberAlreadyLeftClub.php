<?php

namespace App\Domain\Membership\Exceptions;

use DomainException;

final class MemberAlreadyLeftClub extends DomainException
{
    public static function create(): self
    {
        return new self(
            'Das Mitglied ist bereits aus dem Verein ausgetreten.'
        );
    }
}
