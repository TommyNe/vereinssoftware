<?php

namespace App\Application\Membership\Commands;

final readonly class ChangeMemberContactData
{
    public function __construct(
        public string $memberId,
        public ?string $email,
        public ?string $phone,
        public ?string $mobile,
    ) {
    }
}
