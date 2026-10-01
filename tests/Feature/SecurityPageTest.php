<?php

use App\Domain\Audit\Enums\AuditAction;
use App\Filament\Pages\Security;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

test('security page renders the authenticated user sessions', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    DB::table('sessions')->insert([
        'id' => 'session-id',
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
        'payload' => base64_encode('payload'),
        'last_activity' => now()->timestamp,
    ]);

    Livewire::test(Security::class)
        ->assertSee('Test Browser')
        ->assertSee('127.0.0.1');
});

test('security page can revoke another session', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    DB::table('sessions')->insert([
        'id' => 'other-session-id',
        'user_id' => $user->getKey(),
        'payload' => base64_encode('payload'),
        'last_activity' => now()->timestamp,
    ]);

    Livewire::test(Security::class)
        ->call('revokeSession', 'other-session-id');

    expect(DB::table('sessions')->where('id', 'other-session-id')->exists())->toBeFalse();

    $activity = Activity::query()->where('event', AuditAction::SessionRevoked->value)->sole();
    expect($activity->causer_id)->toBe($user->getKey());
    expect($activity->properties->toJson())->not->toContain('other-session-id');
});

test('revoking another users session leaves it intact and creates no audit', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);
    DB::table('sessions')->insert([
        'id' => 'foreign-session-id',
        'user_id' => $otherUser->getKey(),
        'payload' => base64_encode('payload'),
        'last_activity' => now()->timestamp,
    ]);

    Livewire::test(Security::class)->call('revokeSession', 'foreign-session-id');

    $this->assertDatabaseHas('sessions', ['id' => 'foreign-session-id', 'user_id' => $otherUser->getKey()]);
    expect(Activity::query()->where('event', AuditAction::SessionRevoked->value)->exists())->toBeFalse();
});
