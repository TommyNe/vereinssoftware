<?php

use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('finds exactly the rate valid on the requested calendar date', function (string $date, string $expectedPeriod): void {
    $club = Club::factory()->create();
    $type = ContributionType::query()->create(['club_id' => $club->getKey(), 'name' => 'Jahresbeitrag', 'interval' => 'yearly']);
    $rates = [];
    foreach ([
        'past' => ['2025-01-01', '2025-12-31'],
        'current' => ['2026-01-01', '2026-12-31'],
        'future' => ['2027-01-01', null],
    ] as $period => [$from, $until]) {
        $rates[$period] = ContributionRate::query()->create([
            'club_id' => $club->getKey(),
            'contribution_type_id' => $type->getKey(),
            'amount' => '25.00',
            'valid_from' => $from,
            'valid_until' => $until,
        ]);
    }

    $result = ContributionRate::query()->validAt(CarbonImmutable::parse($date))->get();

    expect($result->modelKeys())->toBe([$rates[$expectedPeriod]->getKey()]);
})->with([
    'historical date' => ['2025-06-15', 'past'],
    'first day' => ['2026-01-01', 'current'],
    'within period' => ['2026-06-15', 'current'],
    'last day at midnight' => ['2026-12-31', 'current'],
    'last day in afternoon' => ['2026-12-31 15:30:00', 'current'],
    'next period' => ['2027-01-01', 'future'],
    'open end' => ['2030-06-15', 'future'],
]);
