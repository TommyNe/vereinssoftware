<?php

namespace App\Domain\Membership\Exceptions;

use DomainException;

final class MemberNotInDepartment extends DomainException
{
    public static function create(): self
    {
        return new self(
            'Das Mitglied gehört dieser Abteilung nicht an.'
        );
    }
}
