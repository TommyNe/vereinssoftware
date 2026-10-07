<?php

use App\Domain\Club\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('renders the manual with tables and working section anchors for a club user without administrative permissions', function (): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);

    $response = $this->actingAs($user)->get(route('filament.admin.pages.benutzerhandbuch', ['tenant' => $club]));

    $response->assertSuccessful()
        ->assertSee('Vereinssoftware – Benutzerhandbuch')
        ->assertSee('<table>', false)
        ->assertSee('href="#7-beitr%C3%A4ge-und-forderungen-verwalten"', false)
        ->assertSee('id="7-beiträge-und-forderungen-verwalten"', false)
        ->assertDontSee('href="PROJEKT-DOKUMENTATION.md"', false)
        ->assertDontSee('href="../CHANGELOG.md"', false);
});

it('includes the manual in the filament navigation', function (): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);

    $response = $this->actingAs($user)->get(route('filament.admin.pages.dashboard', ['tenant' => $club]));

    $response->assertSee('Benutzerhandbuch')
        ->assertSee(route('filament.admin.pages.benutzerhandbuch', ['tenant' => $club]), false);
});

it('requires authentication to open the manual', function (): void {
    $club = Club::factory()->create();

    $this->get(route('filament.admin.pages.benutzerhandbuch', ['tenant' => $club]))
        ->assertRedirect(route('filament.admin.auth.login'));
});

it('rejects access through a foreign club', function (): void {
    $club = Club::factory()->create();
    $foreignClub = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);

    $this->actingAs($user)->get(route('filament.admin.pages.benutzerhandbuch', ['tenant' => $foreignClub]))
        ->assertNotFound();
});

it('renders the current markdown file without executing html or unsafe links', function (): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    File::partialMock()
        ->shouldReceive('get')
        ->with(base_path('docs/BENUTZERHANDBUCH.md'))
        ->once()
        ->andReturn("# Aktualisierte Anleitung\n\n<script>alert('unsafe')</script>\n\n[Unsicher](javascript:alert(1))\n\n**Neue Hinweise**");

    $response = $this->actingAs($user)->get(route('filament.admin.pages.benutzerhandbuch', ['tenant' => $club]));

    $response->assertSee('Aktualisierte Anleitung')
        ->assertSee('<strong>Neue Hinweise</strong>', false)
        ->assertDontSee("<script>alert('unsafe')</script>", false)
        ->assertDontSee('href="javascript:', false);
});
