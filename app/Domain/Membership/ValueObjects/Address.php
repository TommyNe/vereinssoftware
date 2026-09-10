<?php

namespace App\Domain\Membership\ValueObjects;

use InvalidArgumentException;

final readonly class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $houseNumber,
        public readonly string $postalCode,
        public readonly string $city,
        public readonly string $countryCode = 'DE'
    ) {
        if (trim($street) === '') {
            throw new InvalidArgumentException(
                'Die Straße darf nicht leer sein.'
            );
        }

        if (trim($houseNumber) === '') {
            throw new InvalidArgumentException(
                'Die Hausnummer darf nicht leer sein.'
            );
        }

        if (trim($postalCode) === '') {
            throw new InvalidArgumentException(
                'Die Postleitzahl darf nicht leer sein.'
            );
        }

        if (trim($city) === '') {
            throw new InvalidArgumentException(
                'Der Ort darf nicht leer sein.'
            );
        }

        if (strlen($countryCode) !== 2) {
            throw new InvalidArgumentException(
                'Der Ländercode muss aus zwei Zeichen bestehen.'
            );
        }
    }

    public function equals(self $other): bool
    {
        return $this->street === $other->street
            && $this->houseNumber === $other->houseNumber
            && $this->postalCode === $other->postalCode
            && $this->city === $other->city
            && $this->countryCode === $other->countryCode;
    }

    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'houseNumber' => $this->houseNumber,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'countryCode' => $this->countryCode,
        ];
    }
}
