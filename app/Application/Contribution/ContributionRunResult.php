<?php

namespace App\Application\Contribution;

final class ContributionRunResult
{
    public int $membersProcessed = 0;

    public int $chargesCreated = 0;

    public int $membersExempt = 0;

    public int $duplicatesSkipped = 0;

    public int $errorsCount = 0;

    public string $totalAmount = '0.00';
}
