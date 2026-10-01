<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\RunContributions;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionRunStatus;
use App\Domain\Contribution\Enums\MemberContributionOverrideType;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionRun;
use App\Domain\Contribution\Models\ContributionRunError;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\MemberContributionOverride;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MembershipType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

/**
 * @return array{club: Club, type: ContributionType, user: User}
 */
function runContributionFixture(): array
{
    $club = Club::factory()->create();
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
        'is_active' => true,
    ]);
    $user = User::factory()->create();

    app(CurrentClub::class)->set($club);

    return compact('club', 'type', 'user');
}

function runContributionMember(array $fixture, array $attributes = []): Member
{
    return Member::factory()->create([
        'club_id' => $fixture['club']->getKey(),
        ...$attributes,
    ]);
}

function runContributionRate(
    ContributionType $type,
    string $amount = '25.00',
    string $validFrom = '2026-01-01',
    ?string $validUntil = null,
    ?MembershipType $membershipType = null,
): void {
    $type->rates()->create([
        'club_id' => $type->club_id,
        'membership_type_id' => $membershipType?->getKey(),
        'amount' => $amount,
        'valid_from' => $validFrom,
        'valid_until' => $validUntil,
    ]);
}

function runContribution(
    array $fixture,
    string $calculationDate = '2026-06-01',
    string $periodFrom = '2026-01-01',
    ?string $periodUntil = '2026-12-31',
    string $dueDate = '2026-01-31',
): ContributionRun {
    return app(RunContributions::class)->handle(
        contributionType: $fixture['type'],
        calculationDate: CarbonImmutable::parse($calculationDate),
        periodFrom: CarbonImmutable::parse($periodFrom),
        periodUntil: $periodUntil === null ? null : CarbonImmutable::parse($periodUntil),
        dueDate: CarbonImmutable::parse($dueDate),
        description: 'Jahresbeitrag 2026',
        createdBy: $fixture['user'],
    );
}

it('belongs to the current club', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type']);

    $run = runContribution($fixture);

    expect($run->club_id)->toBe($fixture['club']->getKey());
});

it('rejects a contribution type from another club', function (): void {
    $fixture = runContributionFixture();
    $foreignClub = Club::factory()->create();
    $fixture['type'] = ContributionType::query()->create([
        'club_id' => $foreignClub->getKey(),
        'name' => 'Fremder Beitrag',
        'interval' => 'yearly',
    ]);

    expect(fn () => runContribution($fixture))
        ->toThrow(DomainException::class, 'Die Beitragsart gehört nicht zum aktuellen Verein.');
});

it('rejects an inactive contribution type', function (): void {
    $fixture = runContributionFixture();
    $fixture['type']->update(['is_active' => false]);

    expect(fn () => runContribution($fixture))
        ->toThrow(DomainException::class, 'Die Beitragsart ist nicht aktiv.');
});

it('validates the contribution period', function (string $periodFrom, ?string $periodUntil, string $dueDate, string $message): void {
    $fixture = runContributionFixture();

    expect(fn () => runContribution($fixture, periodFrom: $periodFrom, periodUntil: $periodUntil, dueDate: $dueDate))
        ->toThrow(DomainException::class, $message);
})->with([
    'end before start' => [
        '2026-01-02',
        '2026-01-01',
        '2026-01-31',
        'Das Periodenende darf nicht vor dem Periodenbeginn liegen.',
    ],
    'due date before start' => [
        '2026-01-02',
        '2026-12-31',
        '2026-01-01',
        'Das Fälligkeitsdatum darf nicht vor dem Periodenbeginn liegen.',
    ],
]);

it('processes active and suspended members and ignores left and future members', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type']);
    $active = runContributionMember($fixture);
    $suspended = runContributionMember($fixture, ['status' => MembershipStatus::Suspended]);
    $left = runContributionMember($fixture, ['status' => MembershipStatus::Left]);
    $future = runContributionMember($fixture, ['joined_at' => '2027-01-01']);

    $run = runContribution($fixture);

    expect($run->members_processed)->toBe(2)
        ->and($run->charges_created)->toBe(2)
        ->and(ContributionCharge::query()->pluck('member_id')->all())
        ->toEqualCanonicalizing([$active->getKey(), $suspended->getKey()])
        ->and(ContributionCharge::query()->whereIn('member_id', [$left->getKey(), $future->getKey()])->count())
        ->toBe(0);
});

it('creates a charge using the normal rate', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type'], '25.00');
    $member = runContributionMember($fixture);

    $run = runContribution($fixture);

    expect(ContributionCharge::query()->where('member_id', $member->getKey())->sole()->amount)
        ->toBe('25.00');
    expect($run->fresh()->contributionType->is($fixture['type']))->toBeTrue();
});

it('uses a fixed individual amount', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type'], '25.00');
    $member = runContributionMember($fixture);
    MemberContributionOverride::query()->create([
        'club_id' => $fixture['club']->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $fixture['type']->getKey(),
        'type' => MemberContributionOverrideType::FixedAmount,
        'amount' => '17.50',
        'valid_from' => '2026-01-01',
    ]);

    runContribution($fixture);

    expect(ContributionCharge::query()->where('member_id', $member->getKey())->sole()->amount)
        ->toBe('17.50');
});

it('creates an exempt charge with zero amount', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type'], '25.00');
    $member = runContributionMember($fixture);
    MemberContributionOverride::query()->create([
        'club_id' => $fixture['club']->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $fixture['type']->getKey(),
        'type' => MemberContributionOverrideType::Exempt,
        'valid_from' => '2026-01-01',
    ]);

    runContribution($fixture);

    expect(ContributionCharge::query()->where('member_id', $member->getKey())->sole()->amount)
        ->toBe('0.00');
});

it('keeps historical rates when a later rate exists', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type'], '25.00', '2026-01-01', '2026-06-30');
    runContributionRate($fixture['type'], '30.00', '2026-07-01');
    runContributionMember($fixture);

    runContribution($fixture, calculationDate: '2026-06-01');

    expect(ContributionCharge::query()->sole()->amount)->toBe('25.00');
});

it('skips an existing contribution charge', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type']);
    $member = runContributionMember($fixture);
    ContributionCharge::query()->create([
        'club_id' => $fixture['club']->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $fixture['type']->getKey(),
        'status' => 'open',
        'amount' => '25.00',
        'description' => 'Bestehende Forderung',
        'period_from' => '2026-01-01',
        'period_until' => '2026-12-31',
        'due_date' => '2026-01-31',
    ]);

    $run = runContribution($fixture);

    expect($run->duplicates_skipped)->toBe(1)
        ->and($run->charges_created)->toBe(0)
        ->and(ContributionCharge::query()->count())->toBe(1);
});

it('does not create the same charge twice across runs', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type']);
    runContributionMember($fixture);

    runContribution($fixture);
    $secondRun = runContribution($fixture);

    expect($secondRun->duplicates_skipped)->toBe(1)
        ->and(ContributionCharge::query()->count())->toBe(1);
});

it('records a missing rate as an error and continues with other members', function (): void {
    $fixture = runContributionFixture();
    $validMembershipType = MembershipType::query()->create([
        'club_id' => $fixture['club']->getKey(),
        'name' => 'Aktiv',
    ]);
    $invalidMembershipType = MembershipType::query()->create([
        'club_id' => $fixture['club']->getKey(),
        'name' => 'Ohne Satz',
    ]);
    $validMember = runContributionMember($fixture, ['membership_type_id' => $validMembershipType->getKey()]);
    $invalidMember = runContributionMember($fixture, ['membership_type_id' => $invalidMembershipType->getKey()]);
    runContributionRate($fixture['type'], membershipType: $validMembershipType);

    $run = runContribution($fixture);

    expect($run->status)->toBe(ContributionRunStatus::CompletedWithErrors)
        ->and($run->errors_count)->toBe(1)
        ->and($run->charges_created)->toBe(1)
        ->and((string) ContributionRunError::query()->sole()->member_id)->toBe((string) $invalidMember->getKey())
        ->and((string) ContributionCharge::query()->sole()->member_id)->toBe((string) $validMember->getKey())
        ->and(ContributionRunError::query()->sole()->message)
        ->toContain('kein gültiger Beitragssatz');

    expect(ContributionRunError::query()->sole()->contributionRun->is($run))->toBeTrue();
});

it('completes with errors when a member has no valid rate', function (): void {
    $fixture = runContributionFixture();
    $membershipType = MembershipType::query()->create([
        'club_id' => $fixture['club']->getKey(),
        'name' => 'Ohne Satz',
    ]);
    $member = runContributionMember($fixture, ['membership_type_id' => $membershipType->getKey()]);

    $run = runContribution($fixture);

    expect($run->status)->toBe(ContributionRunStatus::CompletedWithErrors)
        ->and($run->errors_count)->toBe(1)
        ->and($run->charges_created)->toBe(0)
        ->and((string) ContributionRunError::query()->sole()->member_id)->toBe((string) $member->getKey())
        ->and(ContributionRunError::query()->sole()->message)
        ->toContain('kein gültiger Beitragssatz');
});

it('calculates run totals', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type'], '25.00');
    runContributionMember($fixture);
    $exemptMember = runContributionMember($fixture);
    MemberContributionOverride::query()->create([
        'club_id' => $fixture['club']->getKey(),
        'member_id' => $exemptMember->getKey(),
        'contribution_type_id' => $fixture['type']->getKey(),
        'type' => MemberContributionOverrideType::Exempt,
        'valid_from' => '2026-01-01',
    ]);

    $run = runContribution($fixture);

    expect($run->charges_created)->toBe(2)
        ->and($run->members_exempt)->toBe(1)
        ->and($run->duplicates_skipped)->toBe(0)
        ->and($run->errors_count)->toBe(0)
        ->and($run->total_amount)->toBe('25.00');
});

it('processes only members from the current club', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type']);
    runContributionMember($fixture);
    $foreignClub = Club::factory()->create();
    $foreignMember = Member::factory()->create(['club_id' => $foreignClub->getKey()]);

    $run = runContribution($fixture);

    expect($run->members_processed)->toBe(1)
        ->and(ContributionCharge::query()->where('member_id', $foreignMember->getKey())->exists())
        ->toBeFalse();
});

it('logs contribution run and charge audit activities', function (): void {
    $fixture = runContributionFixture();
    runContributionRate($fixture['type']);
    runContributionMember($fixture);

    $run = runContribution($fixture);

    expect(Activity::query()->pluck('event')->all())
        ->toContain(
            AuditAction::ContributionRunStarted->value,
            AuditAction::ContributionRunCompleted->value,
            AuditAction::ContributionChargeCreated->value,
        );
    expect(Activity::query()->where('subject_id', $run->getKey())->count())->toBe(2);
});
