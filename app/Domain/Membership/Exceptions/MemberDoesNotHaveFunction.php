<?php

namespace App\Domain\Membership\Exceptions;

use DomainException;

final class MemberDoesNotHaveFunction extends DomainException
{
    public static function create(): self
    {
        return new self(
            'Das Mitglied hat diese Funktion aktuell nicht.'
        );
    }
}
