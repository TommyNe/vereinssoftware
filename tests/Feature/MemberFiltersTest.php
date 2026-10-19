<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MembershipType;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 10, 7)->startOfDay());
    $this->club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($this->club);
    setPermissionsTeamId($this->club->getKey());
    $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::MembersView->value, 'web'));
    $this->actingAs($user);
    app(CurrentClub::class)->set($this->club);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant(null);
});

function memberFilterPage(): Testable
{
    Filament::setTenant(test()->club);

    return Livewire::test(ListMembers::class);
}

test('anniversaries include the whole selected calendar year and remain club scoped', function (): void {
    $first = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2001-01-01']);
    $last = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2001-12-31']);
    $tenth = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2016-12-31']);
    $before = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2000-12-31']);
    $after = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2002-01-01']);
    $departed = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2001-01-01', 'status' => MembershipStatus::Left]);
    $withExitDate = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2001-01-01', 'left_at' => '2025-01-01']);
    $foreign = Member::factory()->create(['club_id' => Club::factory()->create()->id, 'joined_at' => '2016-01-01']);

    memberFilterPage()
        ->filterTable('membership_anniversary', ['years' => [25, 10], 'year' => 2026])
        ->assertCanSeeTableRecords([$first, $last, $tenth])
        ->assertCanNotSeeTableRecords([$before, $after, $departed, $withExitDate, $foreign])
        ->filterTable('membership_anniversary', ['years' => [25], 'year' => 2027])
        ->assertCanSeeTableRecords([$after])
        ->assertCanNotSeeTableRecords([$first, $last, $tenth]);
});

test('unselected tenure and anniversary filters leave the member list unchanged', function (): void {
    $active = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2026-01-01']);
    $departed = Member::factory()->create(['club_id' => $this->club->id, 'status' => MembershipStatus::Left]);

    memberFilterPage()
        ->assertCanSeeTableRecords([$active, $departed])
        ->filterTable('membership_anniversary', ['years' => [], 'year' => 2027])
        ->filterTable('membership_years', null)
        ->assertCanSeeTableRecords([$active, $departed]);
});

test('minimum tenure counts completed years inclusively and excludes departed members', function (): void {
    $older = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2016-10-06']);
    $exact = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2016-10-07']);
    $younger = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2016-10-08']);
    $departed = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2001-01-01', 'status' => MembershipStatus::Left]);
    $withExitDate = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2001-01-01', 'left_at' => '2025-01-01']);

    memberFilterPage()
        ->filterTable('membership_years', 10)
        ->assertCanSeeTableRecords([$older, $exact])
        ->assertCanNotSeeTableRecords([$younger, $departed, $withExitDate]);
});

test('minimum tenure handles leap day without overflowing into March', function (): void {
    $this->travelTo(now()->setDate(2024, 2, 29));
    $exact = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2019-02-28']);
    $younger = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2019-03-01']);

    memberFilterPage()
        ->filterTable('membership_years', 5)
        ->assertCanSeeTableRecords([$exact])
        ->assertCanNotSeeTableRecords([$younger]);
});

test('entry dates can be filtered with inclusive and open ended ranges', function (array $range, array $visible, array $hidden): void {
    $members = collect(['2000-12-31', '2001-01-01', '2001-12-31', '2002-01-01'])
        ->map(fn (string $date): Member => Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => $date]));

    memberFilterPage()
        ->filterTable('joined_at', $range)
        ->assertCanSeeTableRecords($members->only($visible))
        ->assertCanNotSeeTableRecords($members->only($hidden));
})->with([
    'both boundaries' => [['from' => '2001-01-01', 'until' => '2001-12-31'], [1, 2], [0, 3]],
    'start only' => [['from' => '2001-01-01'], [1, 2, 3], [0]],
    'end only' => [['until' => '2001-12-31'], [0, 1, 2], [3]],
]);

test('member status can be selected', function (MembershipStatus $status): void {
    $matching = Member::factory()->create(['club_id' => $this->club->id, 'status' => $status]);
    $others = collect(MembershipStatus::cases())
        ->reject(static fn (MembershipStatus $other): bool => $other === $status)
        ->map(fn (MembershipStatus $other): Member => Member::factory()->create(['club_id' => $this->club->id, 'status' => $other]));

    memberFilterPage()
        ->filterTable('status', $status->value)
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords($others);
})->with(MembershipStatus::cases());

test('membership type combines with anniversary filtering without exposing another club', function (): void {
    $type = MembershipType::query()->create(['club_id' => $this->club->id, 'name' => 'Ordentlich']);
    $foreignType = MembershipType::query()->create(['club_id' => Club::factory()->create()->id, 'name' => 'Fremde Mitgliedsart']);
    $matching = Member::factory()->create(['club_id' => $this->club->id, 'membership_type_id' => $type->id, 'joined_at' => '2001-06-01']);
    $otherType = Member::factory()->create(['club_id' => $this->club->id, 'joined_at' => '2001-06-01']);
    $otherYear = Member::factory()->create(['club_id' => $this->club->id, 'membership_type_id' => $type->id, 'joined_at' => '2002-06-01']);
    $foreign = Member::factory()->create(['club_id' => $foreignType->club_id, 'membership_type_id' => $foreignType->id, 'joined_at' => '2001-06-01']);

    memberFilterPage()
        ->assertSee('Ordentlich')
        ->assertDontSee('Fremde Mitgliedsart')
        ->filterTable('membership_type_id', $type->id)
        ->filterTable('membership_anniversary', ['years' => [25], 'year' => 2026])
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$otherType, $otherYear, $foreign])
        ->filterTable('membership_type_id', $foreignType->id)
        ->assertCanNotSeeTableRecords([$matching, $otherType, $otherYear, $foreign]);
});
