<?php

use App\Application\Club\CurrentClub;
use App\Application\Sepa\ClubSepaConfigurationManager;
use App\Application\Sepa\MandateReferenceGenerator;
use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Domain\Sepa\Models\SepaMandate;
use App\Filament\Pages\SepaSettings;
use App\Models\User;
use App\Policies\ClubSepaConfigurationPolicy;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

/**
 * @param  list<Permission>  $permissions
 * @return array{club: Club, user: User}
 */
function clubSepaConfigurationFixture(array $permissions = []): array
{
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    foreach ($permissions as $permission) {
        $user->givePermissionTo(SpatiePermission::findOrCreate($permission->value, 'web'));
    }
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    return compact('club', 'user');
}

/** @param array<string, mixed> $overrides */
function saveTestClubSepaConfiguration(User $user, array $overrides = []): ClubSepaConfiguration
{
    return app(ClubSepaConfigurationManager::class)->save(...array_replace([
        'creditorIdentifier' => 'DE98ZZZ09999999999',
        'accountHolder' => 'Sportverein Musterstadt',
        'iban' => 'DE89370400440532013000',
        'bic' => 'COBADEFFXXX',
        'mandateReferencePrefix' => 'SV-',
        'defaultLeadDays' => 5,
        'defaultPurpose' => 'Mitgliedsbeitrag',
        'isActive' => true,
        'user' => $user,
    ], $overrides));
}

it('assigns the configuration to the current club', function (): void {
    $fixture = clubSepaConfigurationFixture();

    $configuration = saveTestClubSepaConfiguration($fixture['user']);

    $this->assertDatabaseHas('club_sepa_configurations', [
        'id' => $configuration->getKey(),
        'club_id' => $fixture['club']->getKey(),
        'created_by' => $fixture['user']->getKey(),
        'updated_by' => $fixture['user']->getKey(),
    ]);
    expect($fixture['club']->fresh()->sepaConfiguration->getKey())->toBe($configuration->getKey());
});

it('updates the same record on a second save', function (): void {
    $fixture = clubSepaConfigurationFixture();
    $original = saveTestClubSepaConfiguration($fixture['user']);
    $editor = User::factory()->create();

    $updated = saveTestClubSepaConfiguration($editor, [
        'accountHolder' => 'Neuer Kontoinhaber',
        'defaultLeadDays' => 10,
        'isActive' => false,
    ]);

    expect($updated->getKey())->toBe($original->getKey());
    $this->assertDatabaseCount('club_sepa_configurations', 1);
    $this->assertDatabaseHas('club_sepa_configurations', [
        'id' => $original->getKey(),
        'account_holder' => 'Neuer Kontoinhaber',
        'default_lead_days' => 10,
        'is_active' => false,
        'created_by' => $fixture['user']->getKey(),
        'updated_by' => $editor->getKey(),
    ]);
});

it('rejects a second configuration record for the same club', function (): void {
    $fixture = clubSepaConfigurationFixture();
    $configuration = saveTestClubSepaConfiguration($fixture['user']);
    $duplicate = $configuration->replicate();
    $duplicate->creditor_identifier = 'DE98ZZZ08888888888';

    expect(fn () => $duplicate->save())->toThrow(QueryException::class);

    $this->assertDatabaseCount('club_sepa_configurations', 1);
    $this->assertModelExists($configuration);
});

it('prevents club A from updating the configuration of club B', function (): void {
    $fixture = clubSepaConfigurationFixture([Permission::SepaConfigurationManage]);
    $clubB = Club::factory()->create();
    app(CurrentClub::class)->set($clubB);
    $configurationB = saveTestClubSepaConfiguration($fixture['user']);
    $originalAttributes = $configurationB->fresh()->getRawOriginal();
    app(CurrentClub::class)->set($fixture['club']);

    expect(app(ClubSepaConfigurationPolicy::class)->update($fixture['user'], $configurationB))->toBeFalse();
    $configurationA = saveTestClubSepaConfiguration($fixture['user'], [
        'creditorIdentifier' => 'DE98ZZZ08888888888',
        'accountHolder' => 'Verein A',
    ]);

    expect($configurationA->club_id)->toBe($fixture['club']->getKey());
    expect($configurationB->fresh()->getRawOriginal())->toBe($originalAttributes);
    $this->assertDatabaseCount('club_sepa_configurations', 2);
});

it('decrypts and normalizes the IBAN when read through Eloquent', function (): void {
    $fixture = clubSepaConfigurationFixture();
    $configuration = saveTestClubSepaConfiguration($fixture['user'], ['iban' => ' de89 3704 0044 0532 0130 00 ']);

    $reloaded = ClubSepaConfiguration::query()->findOrFail($configuration->getKey());

    expect($reloaded->iban)->toBe('DE89370400440532013000');
});

it('stores no plaintext IBAN in the database', function (): void {
    $club = Club::factory()->create();
    $configuration = ClubSepaConfiguration::query()->create([
        'club_id' => $club->getKey(),
        'creditor_identifier' => 'DE98ZZZ09999999999',
        'account_holder' => 'Testverein e.V.',
        'iban' => 'DE89370400440532013000',
        'bic' => 'COBADEFFXXX',
        'default_lead_days' => 5,
        'is_active' => true,
    ]);

    $storedIban = DB::table('club_sepa_configurations')->where('id', $configuration->getKey())->value('iban');

    expect($configuration->fresh()->iban)->toBe('DE89370400440532013000');
    expect($storedIban)->toBeString()->not->toBe('DE89370400440532013000')->not->toContain('DE89370400440532013000');
    expect(Crypt::decryptString($storedIban))->toBe('DE89370400440532013000');
});

it('stores the BIC encrypted and decrypts it through Eloquent', function (): void {
    $club = Club::factory()->create();
    $configuration = ClubSepaConfiguration::query()->create([
        'club_id' => $club->getKey(),
        'creditor_identifier' => 'DE98ZZZ09999999999',
        'account_holder' => 'Testverein e.V.',
        'iban' => 'DE89370400440532013000',
        'bic' => 'COBADEFFXXX',
        'default_lead_days' => 5,
        'is_active' => true,
    ]);

    $storedBic = DB::table('club_sepa_configurations')->where('id', $configuration->getKey())->value('bic');

    expect($storedBic)->toBeString()->not->toContain('COBADEFFXXX');
    expect(Crypt::decryptString($storedBic))->toBe('COBADEFFXXX');
    expect($configuration->fresh()->bic)->toBe('COBADEFFXXX');
});

it('rejects invalid configuration data without writing a record', function (array $overrides, string $exception, string $message): void {
    $fixture = clubSepaConfigurationFixture();

    expect(fn () => saveTestClubSepaConfiguration($fixture['user'], $overrides))->toThrow($exception, $message);

    $this->assertDatabaseCount('club_sepa_configurations', 0);
})->with([
    'invalid IBAN format' => [['iban' => 'not-an-iban'], InvalidArgumentException::class, 'Die IBAN hat ein ungültiges Format.'],
    'invalid IBAN checksum' => [['iban' => 'DE88370400440532013000'], InvalidArgumentException::class, 'Die IBAN-Prüfsumme ist ungültig.'],
    'empty creditor identifier' => [['creditorIdentifier' => '  '], InvalidArgumentException::class, 'Die Gläubiger-ID darf nicht leer sein.'],
    'invalid prefix' => [['mandateReferencePrefix' => 'SV/'], DomainException::class, 'Das Mandatspräfix enthält ungültige Zeichen.'],
    'negative lead days' => [['defaultLeadDays' => -1], DomainException::class, 'Die Vorlauftage müssen zwischen 0 und 30 liegen.'],
    'lead days above 30' => [['defaultLeadDays' => 31], DomainException::class, 'Die Vorlauftage müssen zwischen 0 und 30 liegen.'],
]);

it('uses the configured prefix for mandate references', function (): void {
    $fixture = clubSepaConfigurationFixture();
    saveTestClubSepaConfiguration($fixture['user'], ['mandateReferencePrefix' => 'CLUB-']);
    $member = Member::factory()->create(['club_id' => $fixture['club']->getKey(), 'member_number' => '1001']);

    $reference = app(MandateReferenceGenerator::class)->generate($member);

    expect($reference)->toBe('CLUB-1001');
});

it('generates different references for different members', function (): void {
    $fixture = clubSepaConfigurationFixture();
    saveTestClubSepaConfiguration($fixture['user']);
    $first = Member::factory()->create(['club_id' => $fixture['club']->getKey(), 'member_number' => '1001']);
    $second = Member::factory()->create(['club_id' => $fixture['club']->getKey(), 'member_number' => '1002']);

    $generator = app(MandateReferenceGenerator::class);

    expect($generator->generate($first))->toBe('SV-1001');
    expect($generator->generate($second))->toBe('SV-1002');
});

it('adds the next available suffix to an already used reference', function (): void {
    $fixture = clubSepaConfigurationFixture();
    saveTestClubSepaConfiguration($fixture['user']);
    $member = Member::factory()->create(['club_id' => $fixture['club']->getKey(), 'member_number' => '1001']);
    foreach (['SV-1001', 'SV-1001-2'] as $reference) {
        SepaMandate::factory()->create([
            'club_id' => $fixture['club']->getKey(),
            'member_id' => $member->getKey(),
            'mandate_reference' => $reference,
        ]);
    }

    $reference = app(MandateReferenceGenerator::class)->generate($member);

    expect($reference)->toBe('SV-1001-3');
});

it('ignores references belonging to a foreign club', function (): void {
    $fixture = clubSepaConfigurationFixture();
    saveTestClubSepaConfiguration($fixture['user']);
    $member = Member::factory()->create(['club_id' => $fixture['club']->getKey(), 'member_number' => '1001']);
    SepaMandate::factory()->create(['mandate_reference' => 'SV-1001']);

    $reference = app(MandateReferenceGenerator::class)->generate($member);

    expect($reference)->toBe('SV-1001');
});

it('allows users with view permission to see the settings page', function (): void {
    $fixture = clubSepaConfigurationFixture([Permission::SepaConfigurationView]);

    $this->actingAs($fixture['user'])
        ->get(SepaSettings::getUrl(['tenant' => $fixture['club']], panel: 'admin'))
        ->assertOk()
        ->assertSee('SEPA-Konfiguration');
});

it('returns 403 for users without view permission', function (): void {
    $fixture = clubSepaConfigurationFixture([Permission::SepaConfigurationManage]);

    Livewire::actingAs($fixture['user'])->test(SepaSettings::class)->assertForbidden();
});

it('returns 403 when users without manage permission attempt to save', function (): void {
    $fixture = clubSepaConfigurationFixture([Permission::SepaConfigurationView]);
    $configuration = saveTestClubSepaConfiguration($fixture['user']);
    $originalAttributes = $configuration->fresh()->getRawOriginal();

    Livewire::actingAs($fixture['user'])
        ->test(SepaSettings::class)
        ->fillForm(['account_holder' => 'Unzulässige Änderung'])
        ->call('save')
        ->assertForbidden();

    expect($configuration->fresh()->getRawOriginal())->toBe($originalAttributes);
    $this->assertDatabaseCount('club_sepa_configurations', 1);
});

it('returns 403 when an unauthenticated user attempts to save', function (): void {
    expect(fn () => app(SepaSettings::class)->save(app(ClubSepaConfigurationManager::class)))
        ->toThrow(function (HttpException $exception): void {
            expect($exception->getStatusCode())->toBe(403);
        });

    $this->assertDatabaseCount('club_sepa_configurations', 0);
});

it('saves the settings through the page with manage permission', function (): void {
    $fixture = clubSepaConfigurationFixture([Permission::SepaConfigurationView, Permission::SepaConfigurationManage]);

    Livewire::actingAs($fixture['user'])
        ->test(SepaSettings::class)
        ->fillForm([
            'creditor_identifier' => 'DE98ZZZ09999999999',
            'account_holder' => 'Sportverein Musterstadt',
            'iban' => 'DE89370400440532013000',
            'bic' => 'COBADEFFXXX',
            'mandate_reference_prefix' => 'SV-',
            'default_lead_days' => 5,
            'default_purpose' => 'Mitgliedsbeitrag',
            'is_active' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('SEPA-Konfiguration gespeichert');

    $this->assertDatabaseHas('club_sepa_configurations', [
        'club_id' => $fixture['club']->getKey(),
        'account_holder' => 'Sportverein Musterstadt',
        'created_by' => $fixture['user']->getKey(),
    ]);
    $this->assertDatabaseCount('club_sepa_configurations', 1);
});
