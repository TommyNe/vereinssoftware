<?php

namespace App\Domain\Membership\Exceptions;

use DomainException;

class MemberAlreadyRegistered extends DomainException
{
    public static function create(): self
    {
        return new self(
            'Dieses Mitglieder-Aggregat wurde bereits angelegt.'
        );
    }
}
