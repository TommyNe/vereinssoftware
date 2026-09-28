<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;
use App\Policies\ContributionTypePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

it('requires a current club and permission and restricts records to that club', function (bool $hasClub, bool $hasPermission, bool $sameClub): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    setPermissionsTeamId($club->getKey());
    if ($hasPermission) {
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::ContributionTypesManage->value, 'web'));
    }
    if ($hasClub) {
        app(CurrentClub::class)->set($club);
    }
    $type = new ContributionType(['club_id' => $sameClub ? $club->getKey() : Club::factory()->create()->getKey()]);

    $policy = app(ContributionTypePolicy::class);

    expect($policy->viewAny($user))->toBe($hasClub && $hasPermission);
    expect($policy->create($user))->toBe($hasClub && $hasPermission);
    expect($policy->view($user, $type))->toBe($hasClub && $hasPermission && $sameClub);
    expect($policy->update($user, $type))->toBe($hasClub && $hasPermission && $sameClub);
})->with([
    'authorized club' => [true, true, true],
    'missing permission' => [true, false, true],
    'another club' => [true, true, false],
    'missing club context' => [false, true, true],
]);
