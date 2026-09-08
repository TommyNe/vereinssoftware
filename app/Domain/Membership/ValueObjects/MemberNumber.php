<?php

namespace App\Domain\Membership\ValueObjects;

use InvalidArgumentException;

final readonly class MemberNumber implements \Stringable
{
    public function __construct(
        private string $value,
    ) {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException(
                'Eine Mitgliedsnummer darf nicht leer sein.'
            );
        }

        if (mb_strlen($value) > 50) {
            throw new InvalidArgumentException(
                'Eine Mitgliedsnummer darf maximal 50 Zeichen enthalten.'
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
