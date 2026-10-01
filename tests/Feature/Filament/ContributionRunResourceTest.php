<?php

use App\Application\Club\CurrentClub;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionRunStatus;
use App\Domain\Contribution\Enums\MemberContributionOverrideType;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionRun;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\MemberContributionOverride;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MembershipType;
use App\Filament\Resources\ContributionRuns\Pages\ListContributionRuns;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

it('registers the contribution run resource routes', function (): void {
    expect(Route::has('filament.admin.resources.contribution-runs.index'))->toBeTrue()
        ->and(Route::has('filament.admin.resources.contribution-runs.create'))->toBeTrue()
        ->and(Route::has('filament.admin.resources.contribution-runs.view'))->toBeTrue()
        ->and(Route::has('filament.admin.resources.contribution-runs.edit'))->toBeTrue();
});

it('shows contribution runs in the table', function (): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $user->givePermissionTo(
        SpatiePermission::findOrCreate(Permission::ContributionRunsView->value, 'web'),
    );
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
        'is_active' => true,
    ]);
    $run = ContributionRun::query()->create([
        'club_id' => $club->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => ContributionRunStatus::Completed,
        'calculation_date' => '2026-06-01',
        'period_from' => '2026-01-01',
        'period_until' => '2026-12-31',
        'due_date' => '2026-01-31',
        'description' => 'Jahresbeitrag 2026',
        'charges_created' => 12,
        'members_exempt' => 2,
        'duplicates_skipped' => 1,
        'errors_count' => 0,
        'total_amount' => '300.00',
        'created_by' => $user->getKey(),
    ]);

    Livewire::test(ListContributionRuns::class)
        ->assertCanSeeTableRecords([$run])
        ->assertCanRenderTableColumn('contributionType.name')
        ->assertCanRenderTableColumn('status')
        ->assertCanRenderTableColumn('charges_created')
        ->assertCanRenderTableColumn('total_amount')
        ->assertSee('Jahresbeitrag 2026')
        ->assertSee('Abgeschlossen')
        ->assertSee('€300.00');
});

it('runs contributions from the resource action', function (): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $user->givePermissionTo(
        SpatiePermission::findOrCreate(Permission::ContributionRunsView->value, 'web'),
        SpatiePermission::findOrCreate(Permission::ContributionRunsCreate->value, 'web'),
    );
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
        'is_active' => true,
    ]);
    $member = Member::factory()->create(['club_id' => $club->getKey(), 'joined_at' => '2025-01-01']);
    ContributionRate::query()->create([
        'club_id' => $club->getKey(),
        'contribution_type_id' => $type->getKey(),
        'amount' => '25.00',
        'valid_from' => '2026-01-01',
    ]);

    Livewire::test(ListContributionRuns::class)
        ->callAction('runContributions', data: [
            'contribution_type_id' => $type->getKey(),
            'calculation_date' => '2026-06-01',
            'period_from' => '2026-01-01',
            'period_until' => '2026-12-31',
            'due_date' => '2026-01-31',
            'description' => 'Jahresbeitrag 2026',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Beitragslauf abgeschlossen');

    expect(ContributionCharge::query()->where('member_id', $member->getKey())->count())
        ->toBe(1);
});

it('creates four charges totalling 260 euros for full, youth, exempt and overridden members', function (): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $user->givePermissionTo(
        SpatiePermission::findOrCreate(Permission::ContributionRunsView->value, 'web'),
        SpatiePermission::findOrCreate(Permission::ContributionRunsCreate->value, 'web'),
    );
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
        'is_active' => true,
    ]);
    $fullMembershipType = MembershipType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Vollmitglied',
    ]);
    $youthMembershipType = MembershipType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jugendmitglied',
    ]);
    $type->rates()->create([
        'club_id' => $club->getKey(),
        'membership_type_id' => $fullMembershipType->getKey(),
        'amount' => '120.00',
        'valid_from' => '2026-01-01',
    ]);
    $type->rates()->create([
        'club_id' => $club->getKey(),
        'membership_type_id' => $youthMembershipType->getKey(),
        'amount' => '60.00',
        'valid_from' => '2026-01-01',
    ]);

    $fullMember = Member::factory()->create([
        'club_id' => $club->getKey(),
        'membership_type_id' => $fullMembershipType->getKey(),
        'joined_at' => '2025-01-01',
    ]);
    $youthMember = Member::factory()->create([
        'club_id' => $club->getKey(),
        'membership_type_id' => $youthMembershipType->getKey(),
        'joined_at' => '2025-01-01',
    ]);
    $exemptMember = Member::factory()->create([
        'club_id' => $club->getKey(),
        'membership_type_id' => $fullMembershipType->getKey(),
        'joined_at' => '2025-01-01',
    ]);
    $overriddenMember = Member::factory()->create([
        'club_id' => $club->getKey(),
        'membership_type_id' => $fullMembershipType->getKey(),
        'joined_at' => '2025-01-01',
    ]);
    MemberContributionOverride::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $exemptMember->getKey(),
        'contribution_type_id' => $type->getKey(),
        'type' => MemberContributionOverrideType::Exempt,
        'valid_from' => '2026-01-01',
    ]);
    MemberContributionOverride::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $overriddenMember->getKey(),
        'contribution_type_id' => $type->getKey(),
        'type' => MemberContributionOverrideType::FixedAmount,
        'amount' => '80.00',
        'valid_from' => '2026-01-01',
    ]);

    Livewire::test(ListContributionRuns::class)
        ->callAction('runContributions', data: [
            'contribution_type_id' => $type->getKey(),
            'calculation_date' => '2026-06-01',
            'period_from' => '2026-01-01',
            'period_until' => '2026-12-31',
            'due_date' => '2026-01-31',
            'description' => 'Jahresbeitrag 2026',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Beitragslauf abgeschlossen');

    $run = ContributionRun::query()->sole();

    expect($run->status)->toBe(ContributionRunStatus::Completed)
        ->and($run->members_processed)->toBe(4)
        ->and($run->charges_created)->toBe(4)
        ->and($run->members_exempt)->toBe(1)
        ->and($run->duplicates_skipped)->toBe(0)
        ->and($run->errors_count)->toBe(0)
        ->and($run->total_amount)->toBe('260.00');

    expect($run->charges()->pluck('amount', 'member_id')->all())->toEqual([
        (string) $fullMember->getKey() => '120.00',
        (string) $youthMember->getKey() => '60.00',
        (string) $exemptMember->getKey() => '0.00',
        (string) $overriddenMember->getKey() => '80.00',
    ]);

    $activity = Activity::query()->where('event', AuditAction::ContributionRunCompleted->value)->sole();
    expect($activity->causer_id)->toBe($user->getKey())
        ->and($activity->getProperty('club_id'))->toBe($club->getKey())
        ->and($activity->getProperty('charges_created'))->toBe(4)
        ->and($activity->getProperty('members_exempt'))->toBe(1)
        ->and($activity->getProperty('total_amount'))->toBe('260.00');
});
