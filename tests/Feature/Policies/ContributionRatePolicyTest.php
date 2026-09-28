<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;
use App\Policies\ContributionRatePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

it('requires a current club and permission and restricts records to that club', function (bool $hasClub, bool $hasPermission, bool $sameClub): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    setPermissionsTeamId($club->getKey());
    if ($hasPermission) {
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::ContributionRatesManage->value, 'web'));
    }
    if ($hasClub) {
        app(CurrentClub::class)->set($club);
    }
    $rate = new ContributionRate(['club_id' => $sameClub ? $club->getKey() : Club::factory()->create()->getKey()]);

    $policy = app(ContributionRatePolicy::class);

    expect($policy->viewAny($user))->toBe($hasClub && $hasPermission);
    expect($policy->create($user))->toBe($hasClub && $hasPermission);
    expect($policy->view($user, $rate))->toBe($hasClub && $hasPermission && $sameClub);
    expect($policy->update($user, $rate))->toBe($hasClub && $hasPermission && $sameClub);
    expect($policy->delete($user, $rate))->toBeFalse();
    expect($policy->deleteAny($user))->toBeFalse();
    expect($policy->restore($user, $rate))->toBeFalse();
    expect($policy->forceDelete($user, $rate))->toBeFalse();
})->with([
    'authorized club' => [true, true, true],
    'missing permission' => [true, false, true],
    'another club' => [true, true, false],
    'missing club context' => [false, true, true],
]);
