<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;
use App\Policies\PaymentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

it('requires club context and payment permissions and restricts payments to that club', function (bool $hasClub, bool $hasPermission, bool $sameClub): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    setPermissionsTeamId($club->getKey());
    if ($hasPermission) {
        foreach ([Permission::PaymentsView, Permission::PaymentsManage, Permission::PaymentsReverse] as $permission) {
            $user->givePermissionTo(SpatiePermission::findOrCreate($permission->value, 'web'));
        }
    }
    if ($hasClub) {
        app(CurrentClub::class)->set($club);
    }
    $payment = new Payment(['club_id' => $sameClub ? $club->getKey() : Club::factory()->create()->getKey()]);

    $policy = app(PaymentPolicy::class);

    expect($policy->view($user, $payment))->toBe($hasClub && $hasPermission && $sameClub);
    expect($policy->create($user))->toBe($hasClub && $hasPermission);
    expect($policy->reverse($user, $payment))->toBe($hasClub && $hasPermission && $sameClub);
    expect($policy->delete($user, $payment))->toBeFalse();
    expect(Gate::forUser($user)->allows('view', $payment))->toBe($hasClub && $hasPermission && $sameClub);
})->with([
    'authorized current club' => [true, true, true],
    'no permission' => [true, false, true],
    'foreign payment' => [true, true, false],
    'no current club' => [false, true, true],
]);

it('separates viewing managing and reversing payment permissions', function (Permission $permission, bool $canView, bool $canCreate, bool $canReverse): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    setPermissionsTeamId($club->getKey());
    $user->givePermissionTo(SpatiePermission::findOrCreate($permission->value, 'web'));
    app(CurrentClub::class)->set($club);
    $payment = new Payment(['club_id' => $club->getKey()]);

    $policy = app(PaymentPolicy::class);

    expect($policy->view($user, $payment))->toBe($canView);
    expect($policy->create($user))->toBe($canCreate);
    expect($policy->reverse($user, $payment))->toBe($canReverse);
})->with([
    'view only' => [Permission::PaymentsView, true, false, false],
    'manage only' => [Permission::PaymentsManage, false, true, false],
    'reverse only' => [Permission::PaymentsReverse, false, false, true],
]);
