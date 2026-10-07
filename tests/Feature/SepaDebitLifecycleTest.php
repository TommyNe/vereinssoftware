<?php

use App\Application\Club\CurrentClub;
use App\Application\Sepa\ExportSepaDebitRun;
use App\Application\Sepa\PrepareSepaDebitRun;
use App\Application\Sepa\RecordSepaDebitItemEvent;
use App\Application\Sepa\RefreshSepaDebitRunStatus;
use App\Application\Sepa\SepaIdentifierGenerator;
use App\Application\Sepa\SubmitSepaDebitRun;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaDebitEventType;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Enums\SepaDebitSubmissionStatus;
use App\Domain\Sepa\Enums\SepaSubmissionMethod;
use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Domain\Sepa\Models\SepaMandate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createLifecycleRun(Club $club, SepaDebitRunStatus $status = SepaDebitRunStatus::Exported): SepaDebitRun
{
    return SepaDebitRun::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Oktoberbeiträge',
        'collection_date' => '2026-10-15',
        'status' => $status,
        'xml_storage_path' => 'sepa/test.xml.enc',
        'xml_sha256' => str_repeat('a', 64),
    ]);
}

function createLifecycleItem(SepaDebitRun $run, SepaDebitItemStatus $status = SepaDebitItemStatus::Prepared): SepaDebitItem
{
    $member = Member::factory()->create(['club_id' => $run->club_id]);
    $mandate = SepaMandate::factory()->create(['club_id' => $run->club_id, 'member_id' => $member->getKey()]);
    $type = ContributionType::query()->firstOrCreate([
        'club_id' => $run->club_id,
        'name' => 'Jahresbeitrag',
    ], ['interval' => 'yearly', 'is_active' => true]);
    $charge = ContributionCharge::query()->create([
        'club_id' => $run->club_id,
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => ContributionChargeStatus::Open,
        'amount' => '25.00',
        'description' => 'Jahresbeitrag 2026',
        'period_from' => '2026-01-01',
        'period_until' => '2026-12-31',
        'due_date' => '2026-10-15',
    ]);

    return $run->items()->create([
        'club_id' => $run->club_id,
        'contribution_charge_id' => $charge->getKey(),
        'member_id' => $member->getKey(),
        'sepa_mandate_id' => $mandate->getKey(),
        'status' => $status,
        'amount' => $charge->amount,
        'purpose' => $charge->description,
        'account_holder' => $mandate->account_holder,
        'iban' => $mandate->iban,
        'bic' => $mandate->bic,
        'mandate_reference' => $mandate->mandate_reference,
        'mandate_signed_at' => $mandate->signed_at,
    ]);
}

it('submits an exported run with all positions and preserves open charges', function (): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $user = User::factory()->create();
    $run = createLifecycleRun($club);
    $first = createLifecycleItem($run);
    $second = createLifecycleItem($run);
    $otherItem = createLifecycleItem(createLifecycleRun($club));

    $submission = app(SubmitSepaDebitRun::class)->handle(
        $run, SepaSubmissionMethod::BankPortal, CarbonImmutable::parse('2026-10-07 10:30:00'), 'BANK-123', $user,
    );

    expect($submission->status)->toBe(SepaDebitSubmissionStatus::Submitted);
    expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Submitted);
    foreach ([$first, $second] as $item) {
        expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Submitted);
        expect($item->charge->status)->toBe(ContributionChargeStatus::Open);
        $this->assertDatabaseHas('sepa_debit_item_events', [
            'sepa_debit_item_id' => $item->getKey(),
            'type' => 'submitted',
            'occurred_at' => '2026-10-07 10:30:00',
            'recorded_by' => $user->getKey(),
        ]);
    }
    expect($otherItem->fresh()->status)->toBe(SepaDebitItemStatus::Prepared);
    $this->assertDatabaseCount('sepa_debit_submissions', 1);
    $this->assertDatabaseCount('sepa_debit_item_events', 2);
});

it('rejects submission of a run that is not exported without changing its positions', function (SepaDebitRunStatus $status): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $run = createLifecycleRun($club, $status);
    $item = createLifecycleItem($run);
    $user = User::factory()->create();

    expect(fn () => app(SubmitSepaDebitRun::class)->handle(
        $run, SepaSubmissionMethod::BankPortal, CarbonImmutable::parse('2026-10-07'), null, $user,
    ))->toThrow(DomainException::class);

    expect($run->fresh()->status)->toBe($status);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Prepared);
    $this->assertDatabaseCount('sepa_debit_submissions', 0);
    $this->assertDatabaseCount('sepa_debit_item_events', 0);
})->with([
    SepaDebitRunStatus::Draft,
    SepaDebitRunStatus::Prepared,
    SepaDebitRunStatus::Submitted,
    SepaDebitRunStatus::Accepted,
    SepaDebitRunStatus::Rejected,
    SepaDebitRunStatus::PartiallyAccepted,
    SepaDebitRunStatus::Cancelled,
]);

it('rejects a second submission even with a stale exported run', function (): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $run = createLifecycleRun($club);
    $item = createLifecycleItem($run);
    $user = User::factory()->create();
    $service = app(SubmitSepaDebitRun::class);
    $service->handle($run, SepaSubmissionMethod::BankPortal, CarbonImmutable::parse('2026-10-07'), null, $user);

    expect(fn () => $service->handle($run, SepaSubmissionMethod::BankPortal, CarbonImmutable::parse('2026-10-08'), null, $user))
        ->toThrow(DomainException::class);

    $this->assertDatabaseCount('sepa_debit_submissions', 1);
    $this->assertDatabaseCount('sepa_debit_item_events', 1);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Submitted);
});

it('rejects submitting a foreign club run without recording anything', function (): void {
    $run = createLifecycleRun(Club::factory()->create());
    $item = createLifecycleItem($run);
    app(CurrentClub::class)->set(Club::factory()->create());
    $user = User::factory()->create();

    expect(fn () => app(SubmitSepaDebitRun::class)->handle(
        $run, SepaSubmissionMethod::BankPortal, CarbonImmutable::parse('2026-10-07'), null, $user,
    ))->toThrow(DomainException::class);

    $this->assertDatabaseCount('sepa_debit_submissions', 0);
    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Exported);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Prepared);
});

it('rejects submission without complete export metadata', function (string $missingAttribute): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $run = createLifecycleRun($club);
    $run->update([$missingAttribute => null]);
    $user = User::factory()->create();

    expect(fn () => app(SubmitSepaDebitRun::class)->handle(
        $run, SepaSubmissionMethod::BankPortal, CarbonImmutable::parse('2026-10-07'), null, $user,
    ))->toThrow(DomainException::class);

    $this->assertDatabaseCount('sepa_debit_submissions', 0);
    expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Exported);
})->with(['xml_storage_path', 'xml_sha256']);

it('persists allowed item transitions without changing the contribution charge or copying bank data', function (
    SepaDebitItemStatus $current,
    SepaDebitEventType $eventType,
    SepaDebitItemStatus $target,
): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $item = createLifecycleItem(createLifecycleRun($club), $current);
    $user = User::factory()->create();
    $chargeBefore = $item->charge->getAttributes();

    $event = app(RecordSepaDebitItemEvent::class)->handle(
        $item, $eventType, CarbonImmutable::parse('2026-10-20 10:30:00'), ' am04 ', 'Keine Deckung', 'BANK-123', 'manual', $user,
    );

    expect($item->fresh()->status)->toBe($target);
    expect($item->charge->fresh()->getAttributes())->toBe($chargeBefore);
    expect($item->charge->fresh()->status)->toBe(ContributionChargeStatus::Open);
    expect($event->fresh()->getAttributes())->toHaveKeys([
        'club_id', 'sepa_debit_item_id', 'type', 'occurred_at', 'reason_code', 'reason_text', 'bank_reference', 'source', 'recorded_by',
    ]);
    expect(array_intersect(['iban', 'bic', 'account_holder', 'xml', 'xml_content'], array_keys($event->getAttributes())))->toBeEmpty();
    $this->assertDatabaseHas('sepa_debit_item_events', [
        'id' => $event->getKey(),
        'club_id' => $club->getKey(),
        'sepa_debit_item_id' => $item->getKey(),
        'type' => $eventType->value,
        'occurred_at' => '2026-10-20 10:30:00',
        'reason_code' => 'AM04',
        'reason_text' => 'Keine Deckung',
        'bank_reference' => 'BANK-123',
        'source' => 'manual',
        'recorded_by' => $user->getKey(),
    ]);
})->with([
    'submitted to accepted' => [SepaDebitItemStatus::Submitted, SepaDebitEventType::Accepted, SepaDebitItemStatus::Accepted],
    'submitted to rejected' => [SepaDebitItemStatus::Submitted, SepaDebitEventType::Rejected, SepaDebitItemStatus::Rejected],
    'accepted to settled' => [SepaDebitItemStatus::Accepted, SepaDebitEventType::Settled, SepaDebitItemStatus::Settled],
    'settled to returned' => [SepaDebitItemStatus::Settled, SepaDebitEventType::Returned, SepaDebitItemStatus::Returned],
    'settled to refunded' => [SepaDebitItemStatus::Settled, SepaDebitEventType::Refunded, SepaDebitItemStatus::Refunded],
]);

it('rejects forbidden item transitions without changing the item or recording history', function (SepaDebitItemStatus $current, SepaDebitEventType $type): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $item = createLifecycleItem(createLifecycleRun($club), $current);

    expect(fn () => app(RecordSepaDebitItemEvent::class)->handle(
        $item, $type, CarbonImmutable::parse('2026-10-20'), null, null, null, 'manual', null,
    ))->toThrow(DomainException::class);

    expect($item->fresh()->status)->toBe($current);
    $this->assertDatabaseCount('sepa_debit_item_events', 0);
})->with([
    'prepared to returned' => [SepaDebitItemStatus::Prepared, SepaDebitEventType::Returned],
    'rejected to settled' => [SepaDebitItemStatus::Rejected, SepaDebitEventType::Settled],
    'returned to accepted' => [SepaDebitItemStatus::Returned, SepaDebitEventType::Accepted],
]);

it('rejects changing a foreign club item', function (): void {
    $item = createLifecycleItem(createLifecycleRun(Club::factory()->create()), SepaDebitItemStatus::Submitted);
    app(CurrentClub::class)->set(Club::factory()->create());

    expect(fn () => app(RecordSepaDebitItemEvent::class)->handle(
        $item, SepaDebitEventType::Accepted, CarbonImmutable::parse('2026-10-20'), null, null, null, 'manual', null,
    ))->toThrow(DomainException::class);

    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Submitted);
    $this->assertDatabaseCount('sepa_debit_item_events', 0);
});

it('keeps earlier item events unchanged when later events are recorded', function (): void {
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $item = createLifecycleItem(createLifecycleRun($club), SepaDebitItemStatus::Submitted);
    $service = app(RecordSepaDebitItemEvent::class);
    $accepted = $service->handle($item, SepaDebitEventType::Accepted, CarbonImmutable::parse('2026-10-15'), null, null, null, 'manual', null);
    $acceptedBefore = $accepted->fresh()->getAttributes();
    $service->handle($item->fresh(), SepaDebitEventType::Settled, CarbonImmutable::parse('2026-10-16'), null, null, null, 'manual', null);
    $service->handle($item->fresh(), SepaDebitEventType::Returned, CarbonImmutable::parse('2026-10-20'), 'AM04', 'Keine Deckung', null, 'manual', null);

    expect($accepted->fresh()->getAttributes())->toBe($acceptedBefore);
    expect($item->events()->orderBy('occurred_at')->pluck('type')->all())->toBe([
        SepaDebitEventType::Accepted,
        SepaDebitEventType::Settled,
        SepaDebitEventType::Returned,
    ]);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Returned);
});

it('preserves the complete event history for Max Mustermann from preparation through XML export to a returned debit', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-06 12:00:00'));
    Storage::fake('local');
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    $this->actingAs($user);
    ClubSepaConfiguration::query()->create([
        'club_id' => $club->getKey(),
        'creditor_identifier' => 'DE98ZZZ09999999999',
        'account_holder' => 'Sportverein Musterstadt',
        'iban' => 'DE12500105170648489890',
        'bic' => 'INGDDEFFXXX',
        'default_lead_days' => 5,
        'is_active' => true,
    ]);
    $member = Member::factory()->create([
        'club_id' => $club->getKey(),
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
    ]);
    SepaMandate::factory()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'account_holder' => 'Max Mustermann',
        'iban' => 'DE89370400440532013000',
        'bic' => 'COBADEFFXXX',
        'mandate_reference' => 'SV-MAX-001',
        'signed_at' => '2026-10-01',
        'valid_from' => '2026-10-01',
    ]);
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
        'is_active' => true,
    ]);
    $charge = ContributionCharge::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => ContributionChargeStatus::Open,
        'amount' => '120.00',
        'description' => 'Jahresbeitrag 2026',
        'period_from' => '2026-01-01',
        'period_until' => '2026-12-31',
        'due_date' => '2026-10-15',
    ]);
    $chargeBefore = $charge->fresh()->getAttributes();

    $run = app(PrepareSepaDebitRun::class)->handle(
        name: 'Oktoberbeiträge',
        collectionDate: CarbonImmutable::parse('2026-10-15'),
        createdBy: $user,
    );
    $item = $run->items()->sole();
    expect($item->run()->firstOrFail()->getKey())->toBe($run->getKey());
    expect($item->member_id)->toBe((string) $member->getKey());
    expect($item->contribution_charge_id)->toBe((string) $charge->getKey());
    expect($item->amount)->toBe('120.00');
    expect($item->status)->toBe(SepaDebitItemStatus::Prepared);
    expect($charge->fresh()->status)->toBe(ContributionChargeStatus::Open);

    $run = app(ExportSepaDebitRun::class)->handle($run);
    expect($run->status)->toBe(SepaDebitRunStatus::Exported);
    Storage::disk('local')->assertExists($run->xml_storage_path);
    $xml = Crypt::decryptString(Storage::disk('local')->get($run->xml_storage_path));
    expect($xml)->toContain('Max Mustermann')->toContain('120.00');
    expect(hash('sha256', $xml))->toBe($run->xml_sha256);
    $this->assertDatabaseCount('sepa_debit_item_events', 0);

    app(SubmitSepaDebitRun::class)->handle(
        $run, SepaSubmissionMethod::BankPortal, CarbonImmutable::parse('2026-10-07 10:30:00'), 'UPLOAD-123', $user,
    );
    expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Submitted);
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Submitted);
    $submitted = $item->events()->sole();
    expect($submitted->type)->toBe(SepaDebitEventType::Submitted);
    $historyBefore = $item->events()->get()->keyBy('id')->map->getAttributes()->all();

    $service = app(RecordSepaDebitItemEvent::class);
    foreach ([
        [SepaDebitEventType::Accepted, SepaDebitItemStatus::Accepted, '2026-10-08 09:00:00', null, null],
        [SepaDebitEventType::Settled, SepaDebitItemStatus::Settled, '2026-10-15 09:00:00', null, null],
        [SepaDebitEventType::Returned, SepaDebitItemStatus::Returned, '2026-10-20 09:00:00', 'AM04', 'Keine ausreichende Deckung'],
    ] as [$eventType, $status, $occurredAt, $reasonCode, $reasonText]) {
        $service->handle(
            $item->fresh(), $eventType, CarbonImmutable::parse($occurredAt), $reasonCode, $reasonText, 'BANK-123', 'manual', $user,
        );
        expect($item->fresh()->status)->toBe($status);
        $historyAfter = $item->events()->get()->keyBy('id')->map->getAttributes()->all();
        foreach ($historyBefore as $eventId => $attributes) {
            expect($historyAfter[$eventId] ?? null)->toBe($attributes);
        }
        expect($historyAfter)->toHaveCount(count($historyBefore) + 1);
        $historyBefore = $historyAfter;

        if ($status === SepaDebitItemStatus::Accepted) {
            app(RefreshSepaDebitRunStatus::class)->handle($run->fresh());
            expect($run->fresh()->status)->toBe(SepaDebitRunStatus::Accepted);
        }
    }

    expect($item->events()->orderBy('occurred_at')->pluck('type')->all())->toBe([
        SepaDebitEventType::Submitted,
        SepaDebitEventType::Accepted,
        SepaDebitEventType::Settled,
        SepaDebitEventType::Returned,
    ]);
    $this->assertDatabaseCount('sepa_debit_item_events', 4);
    expect($item->events()->where('recorded_by', $user->getKey())->count())->toBe(4);
    expect($charge->fresh()->getAttributes())->toBe($chargeBefore);
});

it('generates distinct stable bank identifiers for different runs and positions', function (): void {
    $club = Club::factory()->create();
    $firstRun = createLifecycleRun($club);
    $secondRun = createLifecycleRun($club);
    $firstItem = createLifecycleItem($firstRun);
    $secondItem = createLifecycleItem($secondRun);
    $generator = app(SepaIdentifierGenerator::class);

    expect($generator->messageId($firstRun))->not->toBe($generator->messageId($secondRun))
        ->toBe($generator->messageId($firstRun->fresh()));
    expect($generator->paymentInformationId($firstRun))->not->toBe($generator->paymentInformationId($secondRun))
        ->toBe($generator->paymentInformationId($firstRun->fresh()));
    expect($generator->endToEndId($firstItem))->not->toBe($generator->endToEndId($secondItem))
        ->toBe($generator->endToEndId($firstItem->fresh()));
});

it('blocks duplicate bank identifiers in the database', function (string $attribute): void {
    $club = Club::factory()->create();
    $firstRun = createLifecycleRun($club);
    $secondRun = createLifecycleRun($club);
    $firstRecord = $attribute === 'end_to_end_id' ? createLifecycleItem($firstRun) : $firstRun;
    $secondRecord = $attribute === 'end_to_end_id' ? createLifecycleItem($secondRun) : $secondRun;
    $firstRecord->update([$attribute => 'UNIQUE-BANK-ID']);

    expect(fn () => $secondRecord->update([$attribute => 'UNIQUE-BANK-ID']))->toThrow(QueryException::class);
    expect($secondRecord->fresh()->getAttribute($attribute))->toBeNull();
})->with(['message_id', 'payment_information_id', 'end_to_end_id']);

it('blocks a second active debit for the same charge in the database', function (SepaDebitItemStatus $status): void {
    $club = Club::factory()->create();
    $item = createLifecycleItem(createLifecycleRun($club), $status);
    $otherRun = createLifecycleRun($club);
    $duplicate = $item->replicate();
    $duplicate->fill(['sepa_debit_run_id' => $otherRun->getKey(), 'status' => SepaDebitItemStatus::Prepared]);

    expect(fn () => $duplicate->save())->toThrow(QueryException::class);
    $this->assertDatabaseCount('sepa_debit_items', 1);
    expect($item->fresh()->status)->toBe($status);
})->with([
    SepaDebitItemStatus::Prepared,
    SepaDebitItemStatus::Submitted,
    SepaDebitItemStatus::Accepted,
    SepaDebitItemStatus::Settled,
]);
