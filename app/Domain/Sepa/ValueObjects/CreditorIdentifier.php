<?php

namespace App\Domain\Sepa\ValueObjects;

use InvalidArgumentException;

final readonly class CreditorIdentifier
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

        if ($normalized === '') {
            throw new InvalidArgumentException(
                'Die Gläubiger-ID darf nicht leer sein.'
            );
        }

        /*
         * Erst einmal konservative
         * Formatprüfung.
         *
         * Die eigentliche fachliche Prüfung
         * kann später länderspezifisch
         * erweitert werden.
         */
        if (
            ! preg_match(
                '/^[A-Z]{2}[0-9A-Z]{5,33}$/',
                $normalized
            )
        ) {
            throw new InvalidArgumentException(
                'Die Gläubiger-ID hat ein ungültiges Format.'
            );
        }

        $this->value =
            $normalized;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
