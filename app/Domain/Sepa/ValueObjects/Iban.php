<?php

namespace App\Domain\Sepa\ValueObjects;

use InvalidArgumentException;

final readonly class Iban
{
    public string $value;

    public function __construct(
        string $value,
    ) {
        $normalized =
            strtoupper(
                preg_replace(
                    '/\s+/',
                    '',
                    trim($value)
                ) ?? ''
            );

        if (
            ! preg_match(
                '/^[A-Z]{2}[0-9A-Z]{13,32}$/',
                $normalized
            )
        ) {
            throw new InvalidArgumentException(
                'Die IBAN hat ein ungültiges Format.'
            );
        }

        if (
            ! self::hasValidChecksum(
                $normalized
            )
        ) {
            throw new InvalidArgumentException(
                'Die IBAN-Prüfsumme ist ungültig.'
            );
        }

        $this->value =
            $normalized;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function masked(): string
    {
        $lastFour = substr($this->value, -4);

        return substr($this->value, 0, 2)
            .'•• •••• •••• •••• '
            .$lastFour;
    }

    private static function hasValidChecksum(
        string $iban,
    ): bool {
        $rearranged =
            substr($iban, 4)
            .substr($iban, 0, 4);

        $numeric = '';

        foreach (
            str_split($rearranged) as $character
        ) {
            if (ctype_digit($character)) {
                $numeric .= $character;

                continue;
            }

            $numeric .=
                (string) (
                    ord($character) - 55
                );
        }

        $remainder = 0;

        foreach (
            str_split($numeric) as $digit
        ) {
            $remainder =
                (
                    $remainder * 10
                    + (int) $digit
                ) % 97;
        }

        return $remainder === 1;
    }
}
