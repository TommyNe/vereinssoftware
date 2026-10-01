<?php

use App\Application\Club\CurrentClub;
use App\Application\Sepa\SepaMandateManager;
use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaMandateStatus;
use App\Domain\Sepa\Models\SepaMandate;
use App\Filament\Resources\Members\Actions\CreateSepaMandateAction;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\Members\Pages\ViewMember;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

/** @return array{member: Member, user: User} */
function sepaAuthorizationFixture(bool $canManage = true): array
{
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $permissions = [Permission::MembersView, Permission::SepaMandatesView];
    if ($canManage) {
        $permissions[] = Permission::SepaMandatesManage;
    }
    foreach ($permissions as $permission) {
        $user->givePermissionTo(SpatiePermission::findOrCreate($permission->value, 'web'));
    }
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    return compact('member', 'user');
}

function createAuthorizationSepaMandate(Member $member, User $user): SepaMandate
{
    return app(SepaMandateManager::class)->create(
        member: $member,
        mandateReference: 'SEPA-AUTH-001',
        accountHolder: 'Max Mustermann',
        iban: 'DE89370400440532013000',
        bic: null,
        signedAt: CarbonImmutable::parse('2026-10-01'),
        validFrom: null,
        createdBy: $user,
    );
}

it('allows a user with management permission to create a mandate from the member page', function (): void {
    $fixture = sepaAuthorizationFixture();

    Livewire::actingAs($fixture['user'])
        ->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->callAction('createSepaMandate', data: [
            'mandate_reference' => 'SEPA-UI-001',
            'account_holder' => 'Max Mustermann',
            'iban' => 'de89 3704 0044 0532 0130 00',
            'signed_at' => '2026-10-01',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('SEPA-Mandat gespeichert');

    $this->assertDatabaseHas('sepa_mandates', [
        'club_id' => $fixture['member']->club_id,
        'member_id' => $fixture['member']->getKey(),
        'created_by' => $fixture['user']->getKey(),
        'mandate_reference' => 'SEPA-UI-001',
        'status' => SepaMandateStatus::Active->value,
    ]);
});

it('prevents a user without management permission from creating a mandate', function (): void {
    $fixture = sepaAuthorizationFixture(canManage: false);

    Livewire::actingAs($fixture['user'])
        ->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->assertActionHidden('createSepaMandate')
        ->call('mountAction', 'createSepaMandate')
        ->call('callMountedAction');

    $callback = CreateSepaMandateAction::make()->getActionFunction();
    expect(fn () => $callback([], $fixture['member'], app(SepaMandateManager::class)))
        ->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('sepa_mandates', 0);
});

it('shows only a masked IBAN to a user without bank data permission', function (): void {
    $fixture = sepaAuthorizationFixture();
    $mandate = createAuthorizationSepaMandate($fixture['member'], $fixture['user']);

    expect(Gate::forUser($fixture['user'])->allows('viewSepaBankData', $fixture['member']))->toBeFalse();
    Livewire::actingAs($fixture['user'])
        ->test(ViewMember::class, ['record' => $fixture['member']->getKey()])
        ->assertSee($mandate->mandate_reference)
        ->assertSee('•••• •••• •••• 3000')
        ->assertDontSee('DE89370400440532013000');
});

it('prevents tenant A from reading or revoking a mandate belonging to tenant B', function (): void {
    $fixture = sepaAuthorizationFixture();
    $clubA = app(CurrentClub::class)->get();
    $fixture['user']->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaBankDataView->value, 'web'));
    $clubB = Club::factory()->create();
    Filament::setTenant($clubB);
    app(CurrentClub::class)->set($clubB);
    $memberB = Member::factory()->create(['club_id' => $clubB->getKey()]);
    $mandateB = createAuthorizationSepaMandate($memberB, $fixture['user']);
    Filament::setTenant($clubA);
    app(CurrentClub::class)->set($clubA);

    $this->actingAs($fixture['user'])
        ->get(MemberResource::getUrl('view', ['tenant' => $clubA, 'record' => $memberB->getKey()]))
        ->assertNotFound();
    foreach (['viewSepaMandates', 'manageSepaMandates', 'viewSepaBankData'] as $ability) {
        expect(Gate::forUser($fixture['user'])->allows($ability, $memberB))->toBeFalse();
    }
    expect(fn () => app(SepaMandateManager::class)->revoke($mandateB, 'Fremdes Mandat', $fixture['user']))
        ->toThrow(DomainException::class, 'Das SEPA-Mandat gehört nicht zum aktuellen Verein.');

    expect($mandateB->fresh()->status)->toBe(SepaMandateStatus::Active);
    expect($mandateB->fresh()->revoked_at)->toBeNull();
});
