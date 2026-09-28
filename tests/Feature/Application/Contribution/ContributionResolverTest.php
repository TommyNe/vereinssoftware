<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\ContributionResolver;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Models\Member;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{Club, ContributionType, Member}
 */
function resolverFixture(): array
{
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);

    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
    ]);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);

    return [$club, $type, $member];
}

it('resolves standard contribution amounts as strings', function (): void {
    [$club, $type, $member] = resolverFixture();
    ContributionRate::query()->create([
        'club_id' => $club->getKey(),
        'contribution_type_id' => $type->getKey(),
        'amount' => '25.00',
        'valid_from' => '2026-01-01',
    ]);

    $amount = app(ContributionResolver::class)->resolveAmount(
        member: $member,
        contributionType: $type,
        date: CarbonImmutable::parse('2026-06-01'),
    );

    expect($amount)->toBe('25.00');
});
