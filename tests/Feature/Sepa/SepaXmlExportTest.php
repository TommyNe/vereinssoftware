<?php

use App\Application\Club\CurrentClub;
use App\Application\Sepa\ExportSepaDebitRun;
use App\Application\Sepa\PrepareSepaDebitRun;
use App\Application\Sepa\Xml\Pain008XmlBuilder;
use App\Application\Sepa\Xml\ValidatePain008Xml;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Enums\SepaMandateStatus;
use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Domain\Sepa\Models\SepaMandate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

/** @return array{club: Club, user: User, configuration: ClubSepaConfiguration, run: SepaDebitRun} */
function sepaXmlExportFixture(int $membersCount = 1, bool $canExport = true): array
{
    test()->travelTo(CarbonImmutable::parse('2026-10-06 12:00:00'));
    Storage::fake('local');
    $club = Club::factory()->create();
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    app(CurrentClub::class)->set($club);
    setPermissionsTeamId($club->getKey());
    if ($canExport) {
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::SepaDebitRunsExport->value, 'web'));
    }
    test()->actingAs($user)->withSession(['current_club_id' => $club->getKey()]);
    $configuration = ClubSepaConfiguration::query()->create([
        'club_id' => $club->getKey(),
        'creditor_identifier' => 'DE98ZZZ09999999999',
        'account_holder' => 'Sportverein Musterstadt',
        'iban' => 'DE12500105170648489890',
        'bic' => 'INGDDEFFXXX',
        'default_lead_days' => 5,
        'is_active' => true,
    ]);
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
        'is_active' => true,
    ]);
    for ($memberNumber = 1; $memberNumber <= $membersCount; $memberNumber++) {
        $member = Member::factory()->create(['club_id' => $club->getKey()]);
        SepaMandate::factory()->create([
            'club_id' => $club->getKey(),
            'member_id' => $member->getKey(),
            'account_holder' => 'Testmitglied '.$memberNumber,
            'iban' => 'DE89370400440532013000',
            'bic' => 'COBADEFFXXX',
            'mandate_reference' => 'SV-000'.$memberNumber,
        ]);
        ContributionCharge::query()->create([
            'club_id' => $club->getKey(),
            'member_id' => $member->getKey(),
            'contribution_type_id' => $type->getKey(),
            'status' => ContributionChargeStatus::Open,
            'amount' => $memberNumber === 1 ? '25.10' : '15.20',
            'description' => 'Jahresbeitrag 2026',
            'period_from' => '2026-01-01',
            'period_until' => '2026-12-31',
            'due_date' => '2026-10-15',
        ]);
    }
    $run = app(PrepareSepaDebitRun::class)->handle(
        name: 'Oktoberbeiträge',
        collectionDate: CarbonImmutable::parse('2026-10-15'),
        createdBy: $user,
    );

    return compact('club', 'user', 'configuration', 'run');
}

function sepaXmlExportDocument(string $xml): DOMDocument
{
    $document = new DOMDocument;
    expect($document->loadXML($xml, LIBXML_NONET))->toBeTrue();

    return $document;
}

/** @param class-string<Throwable> $exceptionClass */
function assertSepaXmlExportRejected(SepaDebitRun $run, string $message, string $exceptionClass = DomainException::class): void
{
    $runAttributes = $run->fresh()->getRawOriginal();
    $itemAttributes = $run->items()->orderBy('id')->get()->map->getRawOriginal()->all();

    expect(fn () => app(ExportSepaDebitRun::class)->handle($run))->toThrow($exceptionClass, $message);

    expect($run->fresh()->getRawOriginal())->toBe($runAttributes);
    expect($run->items()->orderBy('id')->get()->map->getRawOriginal()->all())->toBe($itemAttributes);
    expect(Storage::disk('local')->allFiles())->toBe([]);
    test()->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunExported->value]);
}

it('exports and downloads a prepared run with valid payment data', function (): void {
    ['run' => $preparedRun] = sepaXmlExportFixture();

    $run = app(ExportSepaDebitRun::class)->handle($preparedRun);

    $this->assertDatabaseHas('sepa_debit_runs', [
        'id' => $run->getKey(),
        'status' => SepaDebitRunStatus::Exported->value,
        'xml_format' => 'pain.008.001.08',
        'items_count' => 1,
        'total_amount' => '25.10',
    ]);
    expect($run->xml_storage_path)->toBe('sepa/exports/'.$run->club_id.'/'.$run->getKey().'.xml.enc');
    expect($run->xml_generated_at->equalTo(now()))->toBeTrue();
    expect($run->exported_at->equalTo(now()))->toBeTrue();
    Storage::disk('local')->assertExists($run->xml_storage_path);
    $xml = Crypt::decryptString(Storage::disk('local')->get($run->xml_storage_path));
    expect($run->xml_sha256)->toBe(hash('sha256', $xml));
    $document = sepaXmlExportDocument($xml);
    expect($document->documentElement->namespaceURI)->toBe('urn:iso:std:iso:20022:tech:xsd:pain.008.001.08');
    $this->get(route('sepa-debit-runs.download', $run))
        ->assertOk()
        ->assertDownload('sepa-2026-10-15-'.$run->getKey().'.xml')
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertStreamedContent($xml);
    $item = $run->items()->sole();
    expect($item->status)->toBe(SepaDebitItemStatus::Prepared);
    expect($item->charge->status)->toBe(ContributionChargeStatus::Open);
    expect($item->mandate->status)->toBe(SepaMandateStatus::Active);
});

it('rejects an empty prepared run without exporting anything', function (): void {
    ['run' => $run] = sepaXmlExportFixture(membersCount: 0);

    assertSepaXmlExportRejected($run, 'Der SEPA-Lauf enthält keine Positionen.');
});

it('rejects a run with unresolved preparation errors', function (): void {
    ['run' => $run] = sepaXmlExportFixture();
    $item = $run->items()->sole();
    $run->errors()->create([
        'member_id' => $item->member_id,
        'contribution_charge_id' => $item->contribution_charge_id,
        'message' => 'Ungeklärter Vorbereitungsfehler.',
    ]);
    $run->update(['errors_count' => 1]);

    assertSepaXmlExportRejected($run, 'Der SEPA-Lauf enthält ungeklärte Vorbereitungsfehler.');
});

it('preserves the existing encrypted file and export metadata when exported again', function (): void {
    ['run' => $preparedRun, 'configuration' => $configuration] = sepaXmlExportFixture();
    $run = app(ExportSepaDebitRun::class)->handle($preparedRun);
    $storedFile = Storage::disk('local')->get($run->xml_storage_path);
    $runAttributes = $run->getRawOriginal();
    $configuration->update(['account_holder' => 'Geänderter Vereinsname']);
    $this->travel(1)->days();

    $repeatedRun = app(ExportSepaDebitRun::class)->handle($preparedRun);

    expect($repeatedRun->getRawOriginal())->toBe($runAttributes);
    expect(Storage::disk('local')->get($run->xml_storage_path))->toBe($storedFile);
    expect(Storage::disk('local')->allFiles())->toBe([$run->xml_storage_path]);
});

it('rejects a run whose payment data is no longer exportable', function (Closure $invalidate, string $message): void {
    ['run' => $run] = sepaXmlExportFixture();
    $invalidate($run);

    assertSepaXmlExportRejected($run, $message);
})->with([
    'cancelled run' => [
        fn (SepaDebitRun $run): bool => $run->update(['status' => SepaDebitRunStatus::Cancelled]),
        'Dieser Lastschriftlauf kann nicht exportiert werden.',
    ],
    'draft run' => [
        fn (SepaDebitRun $run): bool => $run->update(['status' => SepaDebitRunStatus::Draft]),
        'Dieser Lastschriftlauf kann nicht exportiert werden.',
    ],
    'paid charge' => [
        fn (SepaDebitRun $run): bool => $run->items()->sole()->charge->update(['status' => ContributionChargeStatus::Paid]),
        'Mindestens eine Forderung ist nicht mehr offen oder falsch zugeordnet.',
    ],
    'cancelled charge' => [
        fn (SepaDebitRun $run): bool => $run->items()->sole()->charge->update(['status' => ContributionChargeStatus::Cancelled]),
        'Mindestens eine Forderung ist nicht mehr offen oder falsch zugeordnet.',
    ],
    'revoked mandate' => [
        fn (SepaDebitRun $run): bool => $run->items()->sole()->mandate->update(['status' => SepaMandateStatus::Revoked]),
        'Mindestens ein SEPA-Mandat ist nicht mehr gültig.',
    ],
    'expired mandate' => [
        fn (SepaDebitRun $run): bool => $run->items()->sole()->mandate->update(['status' => SepaMandateStatus::Expired]),
        'Mindestens ein SEPA-Mandat ist nicht mehr gültig.',
    ],
    'mandate valid after collection' => [
        fn (SepaDebitRun $run): bool => $run->items()->sole()->mandate->update(['valid_from' => '2026-10-16']),
        'Mindestens ein SEPA-Mandat ist nicht mehr gültig.',
    ],
    'cancelled item' => [
        fn (SepaDebitRun $run): bool => $run->items()->sole()->update(['status' => SepaDebitItemStatus::Cancelled]),
        'Der Lauf enthält eine nicht aktive Lastschriftposition.',
    ],
    'incorrect total' => [
        fn (SepaDebitRun $run): bool => $run->update(['total_amount' => '25.11']),
        'Anzahl oder Gesamtsumme des Lastschriftlaufs stimmt nicht.',
    ],
    'incorrect item count' => [
        fn (SepaDebitRun $run): bool => $run->update(['items_count' => 2]),
        'Anzahl oder Gesamtsumme des Lastschriftlaufs stimmt nicht.',
    ],
    'missing debtor BIC' => [
        fn (SepaDebitRun $run): bool => $run->items()->sole()->update(['bic' => null]),
        'Für den Export fehlt eine gültig formatierte BIC.',
    ],
    'inactive configuration' => [
        fn (SepaDebitRun $run): bool => app(CurrentClub::class)->get()->sepaConfiguration->update(['is_active' => false]),
        'Keine gültige SEPA-Konfiguration vorhanden.',
    ],
]);

it('rejects an export belonging to another club', function (): void {
    ['run' => $run] = sepaXmlExportFixture();
    app(CurrentClub::class)->set(Club::factory()->create());

    assertSepaXmlExportRejected($run, 'Der Lastschriftlauf gehört nicht zum aktuellen Verein.');
});

it('rejects an invalid debtor IBAN without storing an export', function (): void {
    ['run' => $run] = sepaXmlExportFixture();
    $run->items()->sole()->update(['iban' => 'DE00370400440532013000']);

    assertSepaXmlExportRejected($run, 'Die IBAN-Prüfsumme ist ungültig.', InvalidArgumentException::class);
});

it('rejects schema-invalid generated XML before storing an export', function (): void {
    ['run' => $run] = sepaXmlExportFixture();
    $run->items()->sole()->update(['mandate_reference' => str_repeat('M', 36)]);

    assertSepaXmlExportRejected($run, 'Das erzeugte SEPA-XML entspricht nicht dem XSD-Schema.');
});

it('contains two distinct debit transactions with exact amounts and SEPA payment instructions', function (): void {
    ['run' => $run, 'configuration' => $configuration] = sepaXmlExportFixture(membersCount: 2);

    $xml = app(Pain008XmlBuilder::class)->build($run, $configuration);

    $document = sepaXmlExportDocument($xml);
    $namespace = 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.08';
    expect($document->getElementsByTagNameNS($namespace, 'DrctDbtTxInf'))->toHaveCount(2);
    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('sepa', $namespace);
    expect($xpath->evaluate('string(//sepa:GrpHdr/sepa:NbOfTxs)'))->toBe('2');
    expect($xpath->evaluate('string(//sepa:GrpHdr/sepa:CtrlSum)'))->toBe('40.30');
    expect($xpath->evaluate('string(//sepa:PmtInf/sepa:NbOfTxs)'))->toBe('2');
    expect($xpath->evaluate('string(//sepa:PmtInf/sepa:CtrlSum)'))->toBe('40.30');
    expect($xpath->evaluate('string(//sepa:PmtMtd)'))->toBe('DD');
    expect($xpath->evaluate('string(//sepa:SvcLvl/sepa:Cd)'))->toBe('SEPA');
    expect($xpath->evaluate('string(//sepa:LclInstrm/sepa:Cd)'))->toBe('CORE');
    expect($xpath->evaluate('string(//sepa:SeqTp)'))->toBe('RCUR');
    expect($xpath->evaluate('string(//sepa:ReqdColltnDt)'))->toBe('2026-10-15');
    expect($xpath->evaluate('string(//sepa:ChrgBr)'))->toBe('SLEV');
    expect($xpath->evaluate('string(//sepa:CdtrAcct/sepa:Id/sepa:IBAN)'))->toBe('DE12500105170648489890');
    $transactions = $xpath->query('//sepa:DrctDbtTxInf');
    $amounts = [];
    $references = [];
    $identifiers = [];
    foreach ($transactions as $transaction) {
        $amounts[] = $xpath->evaluate('string(sepa:InstdAmt)', $transaction);
        $references[] = $xpath->evaluate('string(sepa:DrctDbtTx/sepa:MndtRltdInf/sepa:MndtId)', $transaction);
        $identifiers[] = $xpath->evaluate('string(sepa:PmtId/sepa:EndToEndId)', $transaction);
        expect($xpath->evaluate('string(sepa:InstdAmt/@Ccy)', $transaction))->toBe('EUR');
        expect($xpath->evaluate('string(sepa:DbtrAcct/sepa:Id/sepa:IBAN)', $transaction))->toBe('DE89370400440532013000');
        expect($xpath->evaluate('string(sepa:DrctDbtTx/sepa:MndtRltdInf/sepa:DtOfSgntr)', $transaction))->toBe('2026-10-01');
    }
    expect($amounts)->toEqualCanonicalizing(['25.10', '15.20']);
    expect($references)->toEqualCanonicalizing(['SV-0001', 'SV-0002']);
    expect(array_unique($identifiers))->toHaveCount(2);
});

it('escapes special characters in creditor and debtor names and payment purposes', function (): void {
    ['run' => $run, 'configuration' => $configuration] = sepaXmlExportFixture();
    $creditor = 'Förderverein Müller & Söhne <Sport>';
    $debtor = 'Jörg & René <Müller>';
    $purpose = 'Beitrag für <Sport> & Spaß';
    $configuration->update(['account_holder' => $creditor]);
    $run->items()->sole()->update(['account_holder' => $debtor, 'purpose' => $purpose]);

    $xml = app(Pain008XmlBuilder::class)->build($run, $configuration);

    $document = sepaXmlExportDocument($xml);
    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.08');
    expect($xpath->evaluate('string(//sepa:InitgPty/sepa:Nm)'))->toBe($creditor);
    expect($xpath->evaluate('string(//sepa:Cdtr/sepa:Nm)'))->toBe($creditor);
    expect($xpath->evaluate('string(//sepa:Dbtr/sepa:Nm)'))->toBe($debtor);
    expect($xpath->evaluate('string(//sepa:Ustrd)'))->toBe($purpose);
    expect($xml)->toContain('&amp;', '&lt;Sport&gt;', '&lt;Müller&gt;')
        ->not->toContain($creditor, $debtor, $purpose);
    app(ValidatePain008Xml::class)->validate($xml);
});

it('stores only encrypted XML and verifies the checksum against its decrypted contents', function (): void {
    ['run' => $preparedRun] = sepaXmlExportFixture();

    $run = app(ExportSepaDebitRun::class)->handle($preparedRun);

    $disk = Storage::disk('local');
    expect($disk->allFiles())->toBe([$run->xml_storage_path]);
    $encrypted = $disk->get($run->xml_storage_path);
    expect($encrypted)->not->toContain('<?xml', '<Document', 'DE89370400440532013000', 'DE12500105170648489890', 'COBADEFFXXX', 'Testmitglied 1');
    $xml = Crypt::decryptString($encrypted);
    expect($xml)->toContain('<?xml', '<Document', 'DE89370400440532013000', 'Testmitglied 1');
    expect(hash('sha256', $xml))->toBe($run->xml_sha256);
    app(ValidatePain008Xml::class)->validate($xml);
});

it('validates generated XML against the bundled EPC schema', function (int $membersCount): void {
    ['run' => $run, 'configuration' => $configuration] = sepaXmlExportFixture($membersCount);

    $xml = app(Pain008XmlBuilder::class)->build($run, $configuration);
    app(ValidatePain008Xml::class)->validate($xml);

    $document = sepaXmlExportDocument($xml);
    expect($document->schemaValidate(resource_path('sepa/xsd/EPC130-08_2025_V1.0_pain.008.001.08.xsd')))->toBeTrue();
})->with([1, 2]);

it('rejects syntactically valid XML that violates the EPC schema', function (): void {
    ['run' => $run, 'configuration' => $configuration] = sepaXmlExportFixture();
    $document = sepaXmlExportDocument(app(Pain008XmlBuilder::class)->build($run, $configuration));
    $paymentMethod = $document->getElementsByTagNameNS('urn:iso:std:iso:20022:tech:xsd:pain.008.001.08', 'PmtMtd')->item(0);
    $paymentMethod->parentNode->removeChild($paymentMethod);
    $invalidXml = $document->saveXML();

    expect(fn () => app(ValidatePain008Xml::class)->validate($invalidXml))
        ->toThrow(DomainException::class, 'Das erzeugte SEPA-XML entspricht nicht dem XSD-Schema.');
});

it('rejects a download when its checksum differs from the stored XML', function (): void {
    ['run' => $preparedRun] = sepaXmlExportFixture();
    $run = app(ExportSepaDebitRun::class)->handle($preparedRun);
    $run->update(['xml_sha256' => str_repeat('0', 64)]);
    $storedFile = Storage::disk('local')->get($run->xml_storage_path);

    $this->getJson(route('sepa-debit-runs.download', $run))
        ->assertInternalServerError()
        ->assertJsonPath('message', 'Die Exportdatei ist beschädigt.');

    expect(Storage::disk('local')->get($run->xml_storage_path))->toBe($storedFile);
    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunDownloaded->value]);
});

it('forbids downloading XML without the export permission', function (): void {
    ['run' => $preparedRun] = sepaXmlExportFixture(canExport: false);
    $run = app(ExportSepaDebitRun::class)->handle($preparedRun);

    $this->get(route('sepa-debit-runs.download', $run))->assertForbidden();

    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunDownloaded->value]);
});

it('returns 404 when downloading a run belonging to another club', function (): void {
    ['run' => $preparedRun, 'user' => $user] = sepaXmlExportFixture();
    $run = app(ExportSepaDebitRun::class)->handle($preparedRun);
    $otherClub = Club::factory()->create();
    $user->clubs()->attach($otherClub);
    $this->withSession(['current_club_id' => $otherClub->getKey()]);

    $this->get(route('sepa-debit-runs.download', $run))->assertNotFound();

    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitRunDownloaded->value]);
});
