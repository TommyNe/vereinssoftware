<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\ContributionRateManager;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Models\MembershipType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function contributionTypeInCurrentClub(): ContributionType
{
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);

    return ContributionType::query()->create(['club_id' => $club->getKey(), 'name' => 'Jahresbeitrag', 'interval' => 'yearly']);
}

it('assigns new rates to the current club and persists amounts with two decimal places', function (string $amount, string $expected): void {
    $type = contributionTypeInCurrentClub();

    $rate = app(ContributionRateManager::class)->create($type->getKey(), null, $amount, CarbonImmutable::parse('2026-01-01'), null);

    $this->assertDatabaseHas('contribution_rates', ['id' => $rate->getKey(), 'club_id' => $type->club_id, 'contribution_type_id' => $type->getKey(), 'membership_type_id' => null]);
    expect($rate->fresh()->amount)->toBe($expected);
})->with(['whole euros' => ['12', '12.00'], 'one decimal' => ['12.5', '12.50'], 'two decimals' => ['12.34', '12.34']]);

it('rejects a contribution type belonging to another club without saving a rate', function (): void {
    contributionTypeInCurrentClub();
    $foreignType = ContributionType::query()->create(['club_id' => Club::factory()->create()->getKey(), 'name' => 'Fremd', 'interval' => 'yearly']);

    expect(fn () => app(ContributionRateManager::class)->create($foreignType->getKey(), null, '20.00', CarbonImmutable::parse('2026-01-01'), null))->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseCount('contribution_rates', 0);
});

it('rejects a membership type belonging to another club without saving a rate', function (): void {
    $type = contributionTypeInCurrentClub();
    $membershipType = MembershipType::query()->create(['club_id' => Club::factory()->create()->getKey(), 'name' => 'Fremd']);

    expect(fn () => app(ContributionRateManager::class)->create($type->getKey(), $membershipType->getKey(), '20.00', CarbonImmutable::parse('2026-01-01'), null))->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseCount('contribution_rates', 0);
});

it('rejects an end date before the start date', function (): void {
    $type = contributionTypeInCurrentClub();

    expect(fn () => app(ContributionRateManager::class)->create($type->getKey(), null, '20.00', CarbonImmutable::parse('2026-01-02'), CarbonImmutable::parse('2026-01-01')))
        ->toThrow(DomainException::class, 'Das Gültig-bis-Datum darf nicht vor dem Gültig-ab-Datum liegen.');

    $this->assertDatabaseCount('contribution_rates', 0);
});

it('accepts an end date on or after the start date', function (string $until): void {
    $type = contributionTypeInCurrentClub();

    $rate = app(ContributionRateManager::class)->create($type->getKey(), null, '20.00', CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse($until));

    $this->assertModelExists($rate);
    expect($rate->fresh()->valid_until->toDateString())->toBe($until);
})->with(['same day' => '2026-01-01', 'later day' => '2026-12-31']);

it('rejects overlapping periods for the same contribution and membership type', function (string $from, ?string $until, ?string $existingUntil, bool $specificMembership): void {
    $type = contributionTypeInCurrentClub();
    $membershipId = $specificMembership ? MembershipType::query()->create(['club_id' => $type->club_id, 'name' => 'Aktiv'])->getKey() : null;
    $manager = app(ContributionRateManager::class);
    $existing = $manager->create($type->getKey(), $membershipId, '20.00', CarbonImmutable::parse('2026-03-01'), $existingUntil === null ? null : CarbonImmutable::parse($existingUntil));

    expect(fn () => $manager->create($type->getKey(), $membershipId, '30.00', CarbonImmutable::parse($from), $until === null ? null : CarbonImmutable::parse($until)))
        ->toThrow(DomainException::class, 'Die Gültigkeitszeiträume dürfen sich nicht überschneiden.');

    $this->assertDatabaseCount('contribution_rates', 1);
    expect($existing->fresh()->amount)->toBe('20.00');
})->with([
    'identical periods' => ['2026-03-01', '2026-06-30', '2026-06-30', false],
    'inside existing' => ['2026-04-01', '2026-05-31', '2026-06-30', false],
    'contains existing' => ['2026-01-01', '2026-12-31', '2026-06-30', false],
    'overlaps start' => ['2026-02-01', '2026-04-01', '2026-06-30', false],
    'overlaps end' => ['2026-06-01', '2026-07-31', '2026-06-30', false],
    'touches start' => ['2026-02-01', '2026-03-01', '2026-06-30', false],
    'touches end' => ['2026-06-30', '2026-07-31', '2026-06-30', false],
    'existing open end' => ['2027-01-01', '2027-12-31', null, false],
    'new open end' => ['2026-01-01', null, '2026-06-30', false],
    'both open end' => ['2027-01-01', null, null, false],
    'specific membership' => ['2026-04-01', '2026-05-31', '2026-06-30', true],
]);

it('allows adjacent non-overlapping periods', function (string $from, ?string $until): void {
    $type = contributionTypeInCurrentClub();
    $manager = app(ContributionRateManager::class);
    $manager->create($type->getKey(), null, '20.00', CarbonImmutable::parse('2026-03-01'), CarbonImmutable::parse('2026-06-30'));

    $rate = $manager->create($type->getKey(), null, '30.00', CarbonImmutable::parse($from), $until === null ? null : CarbonImmutable::parse($until));

    $this->assertModelExists($rate);
    $this->assertDatabaseCount('contribution_rates', 2);
})->with(['before' => ['2026-01-01', '2026-02-28'], 'after' => ['2026-07-01', null]]);

it('allows parallel rates for different membership types', function (): void {
    $type = contributionTypeInCurrentClub();
    $active = MembershipType::query()->create(['club_id' => $type->club_id, 'name' => 'Aktiv']);
    $passive = MembershipType::query()->create(['club_id' => $type->club_id, 'name' => 'Passiv']);
    $manager = app(ContributionRateManager::class);
    $manager->create($type->getKey(), $active->getKey(), '30.00', CarbonImmutable::parse('2026-01-01'), null);

    $rate = $manager->create($type->getKey(), $passive->getKey(), '15.00', CarbonImmutable::parse('2026-01-01'), null);

    $this->assertDatabaseHas('contribution_rates', ['id' => $rate->getKey(), 'membership_type_id' => $passive->getKey()]);
    $this->assertDatabaseCount('contribution_rates', 2);
});
