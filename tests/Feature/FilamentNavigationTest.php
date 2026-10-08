<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role as RoleName;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function navigationClub(User $user): Club
{
    $club = Club::factory()->create();
    $user->clubs()->attach($club);
    test()->actingAs($user);
    setPermissionsTeamId($club->getKey());
    app(CurrentClub::class)->set($club);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);

    return $club;
}

/** @return array<string, array<int, string>> */
function visibleNavigationLabels(): array
{
    return collect(Filament::getNavigation())
        ->mapWithKeys(static fn (NavigationGroup $group): array => [
            $group->getLabel() ?? 'Start' => collect($group->getItems())
                ->map(static fn (NavigationItem $item): string => $item->getLabel())
                ->all(),
        ])
        ->all();
}

it('organizes the administrator navigation by daily work, finances, club structure and settings', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    $user = User::factory()->create();
    $club = navigationClub($user);
    $user->assignRole(RoleName::Administrator->value);

    $this->get(route('filament.admin.pages.dashboard', ['tenant' => $club]))
        ->assertOk()
        ->assertSee('Übersicht')
        ->assertSee('SEPA-Lastschriftläufe');

    expect(visibleNavigationLabels())->toBe([
        'Start' => ['Übersicht', 'Mitglieder'],
        'Finanzen' => ['Beitragsläufe', 'SEPA-Lastschriftläufe', 'Beitragsarten', 'Beitragssätze'],
        'Vereinsstruktur' => ['Mitgliedsarten', 'Abteilungen', 'Vereinsfunktionen'],
        'Einstellungen' => ['Benutzer & Rollen', 'Einladungen', 'SEPA-Einstellungen'],
        'Hilfe' => ['Benutzerhandbuch'],
    ]);
});

it('keeps unauthorized areas out of the navigation and personal pages in the user menu', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $club = navigationClub($user);
    $role = Role::query()->create(['name' => 'member-reader', 'guard_name' => 'web', 'club_id' => $club->getKey()]);
    $role->givePermissionTo(Permission::MembersView->value);
    $user->assignRole($role);

    $this->get(route('filament.admin.pages.dashboard', ['tenant' => $club]))->assertOk();

    expect(visibleNavigationLabels())->toBe([
        'Start' => ['Übersicht', 'Mitglieder'],
        'Hilfe' => ['Benutzerhandbuch'],
    ]);
    $userMenu = Filament::getUserMenuItems();
    expect($userMenu['profile']->getUrl())->toBe(route('filament.admin.pages.profile', ['tenant' => $club]));
    $securityUrl = collect($userMenu)->first(static fn (Action $item): bool => $item->getLabel() === 'Sicherheit')?->getUrl();
    expect($securityUrl)->toBe(route('filament.admin.pages.security', ['tenant' => $club]));
    $this->get($securityUrl)->assertOk();
});
