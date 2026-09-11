<?php

namespace App\Domain\Membership\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Left = 'left';
}
