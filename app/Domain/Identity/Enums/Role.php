<?php

namespace App\Domain\Identity\Enums;

enum Role: string
{
    case Administrator = 'administrator';
    case Board = 'board';
    case Treasure = 'treasure';
    case ShootingManager = 'shooting-manager';
    case Member = 'member';
}
