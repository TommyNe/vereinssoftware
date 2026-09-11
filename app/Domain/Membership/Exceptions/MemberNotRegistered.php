<?php

namespace App\Domain\Membership\Exceptions;

final class MemberNotRegistered extends \DomainException
{
    public static function create(): self
    {
        return new self(
            'Das Mitglied wurde noch nicht aufgenommen.'
        );
    }
}
