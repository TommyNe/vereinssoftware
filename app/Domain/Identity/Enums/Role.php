<?php

namespace App\Domain\Identity\Enums;

enum Role: string
{
    case Administrator = 'administrator';
    case Board = 'board';
    case Treasure = 'treasure';
    case ShootingManager = 'shooting-manager';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',

            self::Board => 'Vorstand',

            self::Treasure => 'Kassenwart',

            self::ShootingManager => 'Schießwart',

            self::Member => 'Mitglied',
        };
    }
}
