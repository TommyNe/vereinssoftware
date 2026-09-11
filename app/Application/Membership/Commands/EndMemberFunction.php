<?php

namespace App\Application\Membership\Commands;

use Carbon\CarbonImmutable;

final readonly class EndMemberFunction
{
    public function __construct(
        public string $memberId,
        public string $clubFunctionId,
        public CarbonImmutable $validUntil,
    ) {}
}
