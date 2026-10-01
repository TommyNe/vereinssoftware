<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Filament\Resources\Members\Pages\ViewMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

/**
 * @return array{club: Club, member: Member, user: User, charge: ContributionCharge}
 */
function contributionChargeActionFixture(): array
{
    $club = Club::factory()->create();
    $member = Member::factory()->create([
        'club_id' => $club->getKey(),
    ]);
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
    ]);
    ContributionRate::query()->create([
        'club_id' => $club->getKey(),
        'contribution_type_id' => $type->getKey(),
        'amount' => '25.00',
        'valid_from' => '2026-01-01',
    ]);
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $user->givePermissionTo(
        SpatiePermission::findOrCreate(
            Permission::MembersView->value,
            'web',
        ),
        SpatiePermission::findOrCreate(
            Permission::ContributionChargesManage->value,
            'web',
        ),
    );
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    $charge = ContributionCharge::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => ContributionChargeStatus::Open,
        'amount' => '25.00',
        'description' => 'Jahresbeitrag 2026',
        'period_from' => '2026-01-01',
        'period_until' => '2026-12-31',
        'due_date' => '2026-01-31',
        'created_by' => $user->getKey(),
    ]);

    return compact('club', 'member', 'user', 'charge');
}

it('marks an open contribution charge as paid from the member page', function (): void {
    $fixture = contributionChargeActionFixture();

    Livewire::actingAs($fixture['user'])
        ->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->callAction('markContributionChargePaid', data: [
            'charge_id' => $fixture['charge']->getKey(),
            'paid_at' => '2026-02-15',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Forderung als bezahlt markiert');

    $this->assertDatabaseHas('contribution_charges', [
        'id' => $fixture['charge']->getKey(),
        'status' => ContributionChargeStatus::Paid->value,
        'paid_at' => '2026-02-15 00:00:00',
    ]);
});

it('creates a contribution charge from the member page', function (): void {
    $fixture = contributionChargeActionFixture();

    Livewire::actingAs($fixture['user'])
        ->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->callAction('createContributionCharge', data: [
            'contribution_type_id' => $fixture['charge']->contribution_type_id,
            'calculation_date' => '2027-01-01',
            'period_from' => '2027-01-01',
            'period_until' => '2027-12-31',
            'due_date' => '2027-01-31',
            'description' => 'Jahresbeitrag 2027',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Beitragsforderung erstellt');

    $charge = ContributionCharge::query()
        ->where('description', 'Jahresbeitrag 2027')
        ->sole();

    expect((string) $charge->member_id)->toBe((string) $fixture['member']->getKey());
    expect($charge->period_from->toDateString())->toBe('2027-01-01');
    expect($charge->amount)->toBe('25.00');
});

it('cancels an open contribution charge from the member page', function (): void {
    $fixture = contributionChargeActionFixture();

    Livewire::actingAs($fixture['user'])
        ->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->callAction('cancelContributionCharge', data: [
            'charge_id' => $fixture['charge']->getKey(),
            'reason' => 'Doppelte Erfassung',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Forderung storniert');

    $charge = $fixture['charge']->fresh();
    expect($charge->status)->toBe(ContributionChargeStatus::Cancelled);
    expect($charge->cancellation_reason)->toBe('Doppelte Erfassung');
    expect($charge->cancelled_by)->toBe($fixture['user']->getKey());
    expect($charge->cancelled_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
