<?php

use App\Application\Club\CurrentClub;
use App\Application\Sepa\SepaMandateManager;
use App\Domain\Club\Models\Club;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaMandateStatus;
use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Domain\Sepa\Models\SepaMandate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createSepaMandateWithIban(string $iban): SepaMandate
{
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $user = User::factory()->create();
    app(CurrentClub::class)->set($club);

    return createTestSepaMandate($member, $user, iban: $iban);
}

function createTestSepaMandate(Member $member, User $user, string $reference = 'MANDATE-001', string $iban = 'DE89370400440532013000'): SepaMandate
{
    return app(SepaMandateManager::class)->create(
        member: $member,
        mandateReference: $reference,
        accountHolder: 'Max Mustermann',
        iban: $iban,
        bic: null,
        signedAt: CarbonImmutable::parse('2026-10-01'),
        validFrom: null,
        createdBy: $user,
    );
}

it('stores a valid IBAN normalized and encrypted', function (string $iban): void {
    $mandate = createSepaMandateWithIban($iban);

    $this->assertModelExists($mandate);
    expect($mandate->fresh()->iban)->toBe('DE89370400440532013000');
    expect($mandate->getRawOriginal('iban'))->not->toBe('DE89370400440532013000');
})->with([
    'canonical' => 'DE89370400440532013000',
    'lowercase with spaces' => ' de89 3704 0044 0532 0130 00 ',
]);

it('rejects invalid IBANs without writing a mandate', function (string $iban, string $message): void {
    expect(fn () => createSepaMandateWithIban($iban))
        ->toThrow(InvalidArgumentException::class, $message);

    $this->assertDatabaseCount('sepa_mandates', 0);
})->with([
    'empty' => ['', 'Die IBAN hat ein ungültiges Format.'],
    'invalid format' => ['not-an-iban', 'Die IBAN hat ein ungültiges Format.'],
    'invalid checksum' => ['DE88370400440532013000', 'Die IBAN-Prüfsumme ist ungültig.'],
]);

it('assigns the current club and creator to the mandate', function (): void {
    $mandate = createSepaMandateWithIban('DE89370400440532013000');

    $this->assertDatabaseHas('sepa_mandates', [
        'id' => $mandate->getKey(),
        'club_id' => app(CurrentClub::class)->id(),
        'member_id' => $mandate->member_id,
        'created_by' => $mandate->created_by,
        'status' => SepaMandateStatus::Active->value,
    ]);
    expect($mandate->member->club_id)->toBe(app(CurrentClub::class)->id());
});

it('preserves an explicit mandate reference without requiring a SEPA configuration', function (): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $user = User::factory()->create();

    $mandate = createTestSepaMandate($member, $user, reference: '  EXPLICIT-001  ');

    expect($mandate->mandate_reference)->toBe('EXPLICIT-001');
    $this->assertDatabaseCount('club_sepa_configurations', 0);
    $this->assertDatabaseHas('sepa_mandates', [
        'id' => $mandate->getKey(),
        'mandate_reference' => 'EXPLICIT-001',
    ]);
});

it('generates a reference from the configured prefix when no reference is supplied', function (string $reference): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    ClubSepaConfiguration::query()->create([
        'club_id' => $club->getKey(),
        'creditor_identifier' => 'DE98ZZZ09999999999',
        'account_holder' => 'Testverein e.V.',
        'iban' => 'DE89370400440532013000',
        'mandate_reference_prefix' => 'SV-',
        'is_active' => true,
    ]);
    $member = Member::factory()->create(['club_id' => $club->getKey(), 'member_number' => '1001']);
    $user = User::factory()->create();

    $mandate = createTestSepaMandate($member, $user, reference: $reference);

    expect($mandate->mandate_reference)->toBe('SV-1001');
    $this->assertDatabaseHas('sepa_mandates', [
        'id' => $mandate->getKey(),
        'mandate_reference' => 'SV-1001',
    ]);
})->with(['empty' => '', 'whitespace' => '   ']);

it('rejects automatic reference generation without a SEPA configuration', function (): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $user = User::factory()->create();

    expect(fn () => createTestSepaMandate($member, $user, reference: ''))
        ->toThrow(DomainException::class, 'Für den Verein ist keine SEPA-Konfiguration vorhanden.');

    $this->assertDatabaseCount('sepa_mandates', 0);
});

it('rejects members belonging to another club without writing a mandate', function (): void {
    app(CurrentClub::class)->set(Club::factory()->create());
    $member = Member::factory()->create(['club_id' => Club::factory()->create()->getKey()]);
    $user = User::factory()->create();

    expect(fn () => createTestSepaMandate($member, $user))
        ->toThrow(DomainException::class, 'Das Mitglied gehört nicht zum aktuellen Verein.');

    $this->assertDatabaseCount('sepa_mandates', 0);
});

it('rejects duplicate mandate references within the same club', function (): void {
    $mandate = createSepaMandateWithIban('DE89370400440532013000');
    $otherMember = Member::factory()->create(['club_id' => $mandate->club_id]);

    expect(fn () => createTestSepaMandate($otherMember, $mandate->creator))
        ->toThrow(QueryException::class);

    $this->assertDatabaseCount('sepa_mandates', 1);
    $this->assertModelExists($mandate);
});

it('allows the same mandate reference in different clubs', function (): void {
    $first = createSepaMandateWithIban('DE89370400440532013000');

    $second = createSepaMandateWithIban('DE89370400440532013000');

    expect($first->club_id)->not->toBe($second->club_id);
    expect($second->mandate_reference)->toBe($first->mandate_reference);
    $this->assertModelExists($first);
    $this->assertModelExists($second);
    $this->assertDatabaseCount('sepa_mandates', 2);
});

it('stores no plaintext IBAN in the database and decrypts it when read', function (): void {
    $mandate = SepaMandate::factory()->create([
        'iban' => 'DE89370400440532013000',
    ]);

    $storedIban = DB::table('sepa_mandates')->where('id', $mandate->getKey())->value('iban');
    $reloaded = SepaMandate::query()->findOrFail($mandate->getKey());

    expect($storedIban)->toBeString()->not->toContain('DE89370400440532013000');
    expect($mandate->iban)->toBe('DE89370400440532013000');
    expect($reloaded->iban)->toBe('DE89370400440532013000');
});

it('rejects a second active mandate for the same member', function (): void {
    $mandate = createSepaMandateWithIban('DE89370400440532013000');

    expect(fn () => createTestSepaMandate($mandate->member, $mandate->creator, 'MANDATE-002'))
        ->toThrow(DomainException::class, 'Für dieses Mitglied existiert bereits ein aktives SEPA-Mandat.');

    $this->assertDatabaseCount('sepa_mandates', 1);
    expect($mandate->fresh()->status)->toBe(SepaMandateStatus::Active);
});

it('preserves a revoked mandate with its history', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 12:00:00'));
    $mandate = createSepaMandateWithIban('DE89370400440532013000');
    $user = $mandate->creator;

    app(SepaMandateManager::class)->revoke($mandate, ' Kontowechsel ', $user);

    $this->assertDatabaseHas('sepa_mandates', [
        'id' => $mandate->getKey(),
        'status' => SepaMandateStatus::Revoked->value,
        'revocation_reason' => 'Kontowechsel',
        'revoked_by' => $user->getKey(),
        'mandate_reference' => 'MANDATE-001',
    ]);
    expect($mandate->fresh()->revoked_at->equalTo(now()))->toBeTrue();
    expect($mandate->fresh()->iban)->toBe('DE89370400440532013000');
    expect($mandate->member->fresh()->activeSepaMandate)->toBeNull();
});

it('allows a new mandate after revocation while retaining the old one', function (): void {
    $old = createSepaMandateWithIban('DE89370400440532013000');
    app(SepaMandateManager::class)->revoke($old, 'Neues Mandat', $old->creator);

    $new = createTestSepaMandate($old->member, $old->creator, 'MANDATE-002');

    $this->assertDatabaseCount('sepa_mandates', 2);
    expect($old->fresh()->status)->toBe(SepaMandateStatus::Revoked);
    expect($new->status)->toBe(SepaMandateStatus::Active);
    expect($new->member->fresh()->activeSepaMandate->getKey())->toBe($new->getKey());
    expect($new->member->fresh()->sepaMandates->modelKeys())->toContain($old->getKey(), $new->getKey());
});
