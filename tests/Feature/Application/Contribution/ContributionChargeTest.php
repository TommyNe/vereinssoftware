<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\CancelContributionCharge;
use App\Application\Contribution\CreateContributionCharge;
use App\Application\Contribution\MarkContributionChargePaid;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Enums\MemberContributionOverrideType;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\MemberContributionOverride;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

/**
 * @return array{club: Club, type: ContributionType, member: Member, user: User}
 */
function contributionChargeFixture(): array
{
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);

    return [
        'club' => $club,
        'type' => ContributionType::query()->create([
            'club_id' => $club->getKey(),
            'name' => 'Jahresbeitrag',
            'interval' => 'yearly',
        ]),
        'member' => Member::factory()->create([
            'club_id' => $club->getKey(),
        ]),
        'user' => User::factory()->create(),
    ];
}

/**
 * @param  array{type: ContributionType, member: Member, user: User}  $fixture
 */
function createTestContributionCharge(
    array $fixture,
    ?CarbonImmutable $periodFrom = null,
    ?CarbonImmutable $periodUntil = null,
    ?CarbonImmutable $dueDate = null,
): ContributionCharge {
    return app(CreateContributionCharge::class)->handle(
        member: $fixture['member'],
        contributionType: $fixture['type'],
        calculationDate: CarbonImmutable::parse('2026-06-01'),
        periodFrom: $periodFrom ?? CarbonImmutable::parse('2026-01-01'),
        periodUntil: $periodUntil,
        dueDate: $dueDate ?? CarbonImmutable::parse('2026-01-31'),
        description: 'Jahresbeitrag 2026',
        createdBy: $fixture['user'],
    );
}

function createTestContributionRate(
    ContributionType $type,
    string $amount,
    string $validFrom = '2026-01-01',
    ?string $validUntil = null,
): ContributionRate {
    return ContributionRate::query()->create([
        'club_id' => $type->club_id,
        'contribution_type_id' => $type->getKey(),
        'amount' => $amount,
        'valid_from' => $validFrom,
        'valid_until' => $validUntil,
    ]);
}

it('assigns the current club to a contribution charge', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');

    $charge = createTestContributionCharge($fixture);

    $this->assertDatabaseHas('contribution_charges', [
        'id' => $charge->getKey(),
        'club_id' => $fixture['club']->getKey(),
        'member_id' => $fixture['member']->getKey(),
        'contribution_type_id' => $fixture['type']->getKey(),
    ]);
});

it('rejects a member from another club', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');
    $foreignMember = Member::factory()->create([
        'club_id' => Club::factory()->create()->getKey(),
    ]);

    expect(fn () => app(CreateContributionCharge::class)->handle(
        member: $foreignMember,
        contributionType: $fixture['type'],
        calculationDate: CarbonImmutable::parse('2026-06-01'),
        periodFrom: CarbonImmutable::parse('2026-01-01'),
        periodUntil: null,
        dueDate: CarbonImmutable::parse('2026-01-31'),
        description: 'Jahresbeitrag 2026',
        createdBy: $fixture['user'],
    ))->toThrow(DomainException::class, 'Mitglied und Beitragsart müssen zum aktuellen Verein gehören.');

    $this->assertDatabaseCount('contribution_charges', 0);
});

it('rejects a contribution type from another club', function (): void {
    $fixture = contributionChargeFixture();
    $foreignClub = Club::factory()->create();
    $foreignType = ContributionType::query()->create([
        'club_id' => $foreignClub->getKey(),
        'name' => 'Fremder Beitrag',
        'interval' => 'yearly',
    ]);

    expect(fn () => app(CreateContributionCharge::class)->handle(
        member: $fixture['member'],
        contributionType: $foreignType,
        calculationDate: CarbonImmutable::parse('2026-06-01'),
        periodFrom: CarbonImmutable::parse('2026-01-01'),
        periodUntil: null,
        dueDate: CarbonImmutable::parse('2026-01-31'),
        description: 'Jahresbeitrag 2026',
        createdBy: $fixture['user'],
    ))->toThrow(DomainException::class, 'Mitglied und Beitragsart müssen zum aktuellen Verein gehören.');

    $this->assertDatabaseCount('contribution_charges', 0);
});

it('freezes the standard contribution amount', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');

    $charge = createTestContributionCharge($fixture);

    expect($charge->fresh()->amount)->toBe('25.00');
});

it('freezes a fixed amount override', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');
    MemberContributionOverride::query()->create([
        'club_id' => $fixture['club']->getKey(),
        'member_id' => $fixture['member']->getKey(),
        'contribution_type_id' => $fixture['type']->getKey(),
        'type' => MemberContributionOverrideType::FixedAmount,
        'amount' => '17.50',
        'valid_from' => '2026-01-01',
        'is_active' => true,
        'created_by' => $fixture['user']->getKey(),
    ]);

    $charge = createTestContributionCharge($fixture);

    expect($charge->fresh()->amount)->toBe('17.50');
});

it('creates an exempt contribution charge with amount zero', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');
    MemberContributionOverride::query()->create([
        'club_id' => $fixture['club']->getKey(),
        'member_id' => $fixture['member']->getKey(),
        'contribution_type_id' => $fixture['type']->getKey(),
        'type' => MemberContributionOverrideType::Exempt,
        'amount' => null,
        'valid_from' => '2026-01-01',
        'is_active' => true,
        'created_by' => $fixture['user']->getKey(),
    ]);

    $charge = createTestContributionCharge($fixture);

    expect($charge->fresh()->amount)->toBe('0.00');
});

it('does not change an old charge when a later rate changes', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00', '2026-01-01', '2026-06-30');

    $charge = createTestContributionCharge($fixture);
    createTestContributionRate($fixture['type'], '30.00', '2026-07-01');

    expect($charge->fresh()->amount)->toBe('25.00');
});

it('rejects a period end before the period start', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');

    expect(fn () => createTestContributionCharge(
        fixture: $fixture,
        periodFrom: CarbonImmutable::parse('2026-01-02'),
        periodUntil: CarbonImmutable::parse('2026-01-01'),
    ))->toThrow(DomainException::class, 'Das Periodenende darf nicht vor dem Periodenbeginn liegen.');

    $this->assertDatabaseCount('contribution_charges', 0);
});

it('rejects a due date before the period start', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');

    expect(fn () => createTestContributionCharge(
        fixture: $fixture,
        periodFrom: CarbonImmutable::parse('2026-01-02'),
        dueDate: CarbonImmutable::parse('2026-01-01'),
    ))->toThrow(DomainException::class, 'Das Fälligkeitsdatum darf nicht vor dem Periodenbeginn liegen.');

    $this->assertDatabaseCount('contribution_charges', 0);
});

it('rejects an identical non-cancelled charge', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');
    createTestContributionCharge($fixture);

    expect(fn () => createTestContributionCharge($fixture))
        ->toThrow(DomainException::class, 'Für diesen Zeitraum wurde bereits eine Forderung erzeugt.');

    $this->assertDatabaseCount('contribution_charges', 1);
});

it('allows a new identical charge after cancellation', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');
    $original = createTestContributionCharge($fixture);
    app(CancelContributionCharge::class)->handle($original, 'Korrektur', $fixture['user']);

    $replacement = createTestContributionCharge($fixture);

    $this->assertModelExists($replacement);
    expect($replacement->fresh()->status)->toBe(ContributionChargeStatus::Open);
    $this->assertDatabaseCount('contribution_charges', 2);
});

it('allows an open charge to be paid and stores paid_at', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');
    $charge = createTestContributionCharge($fixture);
    $paidAt = CarbonImmutable::parse('2026-02-15 10:30:00');

    app(MarkContributionChargePaid::class)->handle($charge, $paidAt);

    $charge = $charge->fresh();
    expect($charge->status)->toBe(ContributionChargeStatus::Paid);
    expect($charge->paid_at->toDateTimeString())->toBe($paidAt->toDateTimeString());
    expect(Activity::query()->where('event', AuditAction::ContributionChargePaid->value)->exists())->toBeTrue();
});

it('rejects paying an already paid charge', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');
    $charge = createTestContributionCharge($fixture);
    app(MarkContributionChargePaid::class)->handle($charge, CarbonImmutable::parse('2026-02-15'));

    expect(fn () => app(MarkContributionChargePaid::class)->handle($charge->fresh(), CarbonImmutable::parse('2026-02-16')))
        ->toThrow(DomainException::class, 'Nur offene Forderungen können als bezahlt markiert werden.');

    expect($charge->fresh()->paid_at->toDateString())->toBe('2026-02-15');
});

it('cancels a charge without deleting it and stores the reason', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');
    $charge = createTestContributionCharge($fixture);

    app(CancelContributionCharge::class)->handle($charge, 'Doppelte Erfassung', $fixture['user']);

    $charge = $charge->fresh();
    $this->assertModelExists($charge);
    expect($charge->status)->toBe(ContributionChargeStatus::Cancelled);
    expect($charge->cancellation_reason)->toBe('Doppelte Erfassung');
    expect($charge->cancelled_by)->toBe($fixture['user']->getKey());
    expect($charge->cancelled_at)->not->toBeNull();
    expect(Activity::query()->where('event', AuditAction::ContributionChargeCancelled->value)->exists())->toBeTrue();
});

it('rejects paying or cancelling a charge from another club', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');

    $foreignFixture = contributionChargeFixture();
    createTestContributionRate($foreignFixture['type'], '25.00');
    $foreignCharge = createTestContributionCharge($foreignFixture);
    app(CurrentClub::class)->set($fixture['club']);

    expect(fn () => app(MarkContributionChargePaid::class)->handle($foreignCharge, CarbonImmutable::parse('2026-02-15')))
        ->toThrow(DomainException::class, 'Die Forderung gehört nicht zum aktuellen Verein.');
    expect(fn () => app(CancelContributionCharge::class)->handle($foreignCharge, 'Unzulässig', $fixture['user']))
        ->toThrow(DomainException::class, 'Die Forderung gehört nicht zum aktuellen Verein.');

    expect($foreignCharge->fresh()->status)->toBe(ContributionChargeStatus::Open);
});

it('loads contribution relations through the member_id foreign key', function (): void {
    $fixture = contributionChargeFixture();
    createTestContributionRate($fixture['type'], '25.00');
    MemberContributionOverride::query()->create([
        'club_id' => $fixture['club']->getKey(),
        'member_id' => $fixture['member']->getKey(),
        'contribution_type_id' => $fixture['type']->getKey(),
        'type' => MemberContributionOverrideType::Exempt,
        'valid_from' => '2026-01-01',
        'is_active' => true,
        'created_by' => $fixture['user']->getKey(),
    ]);
    createTestContributionCharge($fixture);

    $member = $fixture['member']->load([
        'contributionOverrides',
        'contributionCharges',
    ]);

    expect($member->contributionOverrides)->toHaveCount(1);
    expect($member->contributionCharges)->toHaveCount(1);
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
