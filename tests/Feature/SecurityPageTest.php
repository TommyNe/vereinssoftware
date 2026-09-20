<?php

use App\Filament\Pages\Security;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

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
});
