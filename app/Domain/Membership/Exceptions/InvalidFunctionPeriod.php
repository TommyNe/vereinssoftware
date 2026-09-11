<?php

namespace App\Domain\Membership\Exceptions;

use DomainException;

final class InvalidFunctionPeriod extends DomainException
{
    public static function create(): self
    {
        return new self(
            'Das Ende der Funktion darf nicht vor ihrem Beginn liegen.'
        );
    }
}
