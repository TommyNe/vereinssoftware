<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\CreatePaymentFromSettledSepaItem;
use App\Application\Contribution\PaymentBalanceResolver;
use App\Application\Contribution\ReversePaymentForSepaReturn;
use App\Application\Sepa\RecordSepaDebitItemEvent;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ChargePaymentState;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Enums\PaymentMethod;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Contribution\Models\PaymentAllocation;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaDebitEventType;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Domain\Sepa\Models\SepaMandate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('rolls back the settlement event and item status when payment allocation fails', function (): void {
    $fixture = sepaPaymentFixture(SepaDebitItemStatus::Accepted);
    $fixture['item']->charge->update(['status' => 'cancelled']);

    expect(fn () => app(RecordSepaDebitItemEvent::class)->handle(
        $fixture['item'], SepaDebitEventType::Settled, CarbonImmutable::parse('2026-10-08'), null, null, null, 'manual', $fixture['user'],
    ))->toThrow(DomainException::class, 'Stornierte Forderungen können keiner Zahlung zugeordnet werden.');

    expect($fixture['item']->fresh()->status)->toBe(SepaDebitItemStatus::Accepted);
    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('payment_allocations', 0);
    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::PaymentCreated->value]);
});

it('does not record a return or change item status when its payment is missing', function (): void {
    $fixture = sepaPaymentFixture();

    expect(fn () => app(RecordSepaDebitItemEvent::class)->handle(
        $fixture['item'], SepaDebitEventType::Returned, CarbonImmutable::parse('2026-10-10'), 'AM04', 'Keine Deckung', null, 'manual', $fixture['user'],
    ))->toThrow(DomainException::class, 'Für diese SEPA-Position wurde keine Zahlung gefunden.');

    expect($fixture['item']->fresh()->status)->toBe(SepaDebitItemStatus::Settled);
    $this->assertDatabaseCount('sepa_debit_item_events', 0);
    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::SepaDebitItemReturned->value]);
});

it('rejects a duplicate settlement from a stale accepted item without duplicating payment or event', function (): void {
    $fixture = sepaPaymentFixture(SepaDebitItemStatus::Accepted);
    $events = app(RecordSepaDebitItemEvent::class);
    $events->handle($fixture['item'], SepaDebitEventType::Settled, CarbonImmutable::parse('2026-10-08'), null, null, null, 'manual', $fixture['user']);

    expect(fn () => $events->handle($fixture['item'], SepaDebitEventType::Settled, CarbonImmutable::parse('2026-10-08'), null, null, null, 'manual', $fixture['user']))
        ->toThrow(DomainException::class);

    $this->assertDatabaseCount('sepa_debit_item_events', 1);
    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseCount('payment_allocations', 1);
});

it('automatically pays a 120 euro charge on SEPA settlement and reopens it on return', function (): void {
    $fixture = sepaPaymentFixture(SepaDebitItemStatus::Accepted);
    $item = $fixture['item'];
    $item->update(['amount' => '120.00']);
    $item->charge->update(['amount' => '120.00']);
    $resolver = app(PaymentBalanceResolver::class);
    $events = app(RecordSepaDebitItemEvent::class);
    expect($item->charge->fresh()->status)->toBe(ContributionChargeStatus::Open);
    $this->assertDatabaseCount('payments', 0);

    $events->handle($item, SepaDebitEventType::Settled, CarbonImmutable::parse('2026-10-08'), null, null, null, 'manual', $fixture['user']);

    $payment = Payment::query()->sole();
    $allocation = PaymentAllocation::query()->sole();
    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Settled);
    expect($payment->amount)->toBe('120.00');
    expect($payment->method)->toBe(PaymentMethod::SepaDirectDebit);
    expect($payment->status)->toBe(PaymentStatus::Booked);
    $this->assertDatabaseHas('payment_allocations', [
        'id' => $allocation->getKey(),
        'payment_id' => $payment->getKey(),
        'contribution_charge_id' => $item->contribution_charge_id,
        'amount' => '120.00',
    ]);
    $charge = $item->charge->fresh();
    expect($resolver->state($charge))->toBe(ChargePaymentState::Paid);
    expect($resolver->outstandingAmount($charge))->toBe('0.00');
    expect($charge->status)->toBe(ContributionChargeStatus::Paid);

    $events->handle($item->fresh(), SepaDebitEventType::Returned, CarbonImmutable::parse('2026-10-10'), 'AM04', 'Keine Deckung', null, 'manual', $fixture['user']);

    expect($item->fresh()->status)->toBe(SepaDebitItemStatus::Returned);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Reversed);
    $this->assertModelExists($allocation);
    $this->assertDatabaseCount('payment_allocations', 1);
    $charge = $item->charge->fresh();
    expect($resolver->state($charge))->toBe(ChargePaymentState::Open);
    expect($resolver->outstandingAmount($charge))->toBe('120.00');
    expect($charge->status)->toBe(ContributionChargeStatus::Open);
    expect($charge->paid_at)->toBeNull();
    $this->assertDatabaseCount('sepa_debit_item_events', 2);
});

it('rejects manually booking a settled item without a recorded user', function (): void {
    $fixture = sepaPaymentFixture();

    expect(fn () => app(CreatePaymentFromSettledSepaItem::class)->handle($fixture['item'], CarbonImmutable::parse('2026-10-08'), null))
        ->toThrow(DomainException::class, 'Für manuell erfasste SEPA-Zahlungen muss ein Benutzer vorhanden sein.');

    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('payment_allocations', 0);
});

/** @return array{item: SepaDebitItem, user: User} */
function sepaPaymentFixture(SepaDebitItemStatus $status = SepaDebitItemStatus::Settled, string $endToEndId = 'E2E-PAYMENT-TEST-001'): array
{
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $user = User::factory()->create();
    $mandate = SepaMandate::factory()->create(['club_id' => $club->getKey(), 'member_id' => $member->getKey()]);
    $type = ContributionType::query()->create(['club_id' => $club->getKey(), 'name' => 'Jahresbeitrag', 'interval' => 'yearly']);
    $charge = ContributionCharge::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => 'open',
        'amount' => '25.00',
        'description' => 'Jahresbeitrag 2026',
        'period_from' => '2026-01-01',
        'due_date' => '2026-10-08',
    ]);
    $run = SepaDebitRun::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Oktoberbeiträge',
        'collection_date' => '2026-10-08',
        'status' => 'accepted',
    ]);
    $item = $run->items()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'contribution_charge_id' => $charge->getKey(),
        'sepa_mandate_id' => $mandate->getKey(),
        'status' => $status,
        'amount' => '25.00',
        'purpose' => $charge->description,
        'account_holder' => $mandate->account_holder,
        'iban' => $mandate->iban,
        'bic' => $mandate->bic,
        'mandate_reference' => $mandate->mandate_reference,
        'mandate_signed_at' => $mandate->signed_at,
        'end_to_end_id' => $endToEndId,
    ]);

    return compact('item', 'user');
}

it('books a settled item as a SEPA payment and automatically allocates it to its charge', function (): void {
    $fixture = sepaPaymentFixture();

    $payment = app(CreatePaymentFromSettledSepaItem::class)->handle($fixture['item'], CarbonImmutable::parse('2026-10-08'), $fixture['user']);

    expect($payment->fresh()->method)->toBe(PaymentMethod::SepaDirectDebit);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Booked);
    expect($payment->fresh()->booking_date->toDateString())->toBe('2026-10-08');
    expect($payment->fresh()->value_date->toDateString())->toBe('2026-10-08');
    $this->assertDatabaseHas('payments', [
        'id' => $payment->getKey(),
        'club_id' => $fixture['item']->club_id,
        'member_id' => $fixture['item']->member_id,
        'amount' => '25.00',
        'reference' => 'E2E-PAYMENT-TEST-001',
        'source_type' => 'sepa_debit_item',
        'source_id' => $fixture['item']->getKey(),
        'created_by' => $fixture['user']->getKey(),
    ]);
    $this->assertDatabaseHas('payment_allocations', [
        'payment_id' => $payment->getKey(),
        'contribution_charge_id' => $fixture['item']->contribution_charge_id,
        'amount' => '25.00',
    ]);
    $this->assertDatabaseHas('contribution_charges', ['id' => $fixture['item']->contribution_charge_id, 'status' => 'paid']);
});

it('creates exactly one payment allocation and audit pair when the settled item is processed again', function (): void {
    $fixture = sepaPaymentFixture();
    $service = app(CreatePaymentFromSettledSepaItem::class);
    $first = $service->handle($fixture['item'], CarbonImmutable::parse('2026-10-08'), $fixture['user']);

    $second = $service->handle($fixture['item']->fresh(), CarbonImmutable::parse('2026-10-09'), $fixture['user']);

    expect($second->getKey())->toBe($first->getKey());
    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseCount('payment_allocations', 1);
    expect(Activity::query()->where('event', AuditAction::PaymentCreated->value)->count())->toBe(1);
    expect(Activity::query()->where('event', AuditAction::PaymentAllocated->value)->count())->toBe(1);
});

it('rejects items that have not settled without recording a payment', function (SepaDebitItemStatus $status): void {
    $fixture = sepaPaymentFixture($status);

    expect(fn () => app(CreatePaymentFromSettledSepaItem::class)->handle($fixture['item'], CarbonImmutable::parse('2026-10-08'), $fixture['user']))
        ->toThrow(DomainException::class, 'Nur ausgeführte SEPA-Positionen können als Zahlung gebucht werden.');

    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('payment_allocations', 0);
})->with(array_values(array_filter(SepaDebitItemStatus::cases(), static fn (SepaDebitItemStatus $status): bool => $status !== SepaDebitItemStatus::Settled)));

it('rolls back the SEPA payment when its charge cannot be allocated', function (): void {
    $fixture = sepaPaymentFixture();
    $fixture['item']->charge->update(['status' => 'cancelled']);

    expect(fn () => app(CreatePaymentFromSettledSepaItem::class)->handle($fixture['item'], CarbonImmutable::parse('2026-10-08'), $fixture['user']))
        ->toThrow(DomainException::class, 'Stornierte Forderungen können keiner Zahlung zugeordnet werden.');

    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('payment_allocations', 0);
    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::PaymentCreated->value]);
});

it('reverses the payment belonging to a returned item while retaining allocation history', function (): void {
    $fixture = sepaPaymentFixture();
    $payment = app(CreatePaymentFromSettledSepaItem::class)->handle($fixture['item'], CarbonImmutable::parse('2026-10-08'), $fixture['user']);
    $allocation = PaymentAllocation::query()->sole();
    $otherFixture = sepaPaymentFixture(endToEndId: 'E2E-OTHER-PAYMENT-001');
    $otherPayment = app(CreatePaymentFromSettledSepaItem::class)->handle($otherFixture['item'], CarbonImmutable::parse('2026-10-08'), $otherFixture['user']);
    app(CurrentClub::class)->set($fixture['item']->member->club);
    $fixture['item']->update(['status' => 'returned']);

    app(ReversePaymentForSepaReturn::class)->handle($fixture['item'], 'Rücklastschrift', $fixture['user']);

    $this->assertDatabaseHas('payments', ['id' => $payment->getKey(), 'status' => 'reversed', 'reversal_reason' => 'Rücklastschrift']);
    $this->assertModelExists($allocation);
    $this->assertDatabaseHas('payment_allocations', ['id' => $allocation->getKey(), 'amount' => '25.00']);
    $this->assertDatabaseHas('contribution_charges', ['id' => $fixture['item']->contribution_charge_id, 'status' => 'open', 'paid_at' => null]);
    expect($otherPayment->fresh()->status)->toBe(PaymentStatus::Booked);
});

it('rejects a return without a matching SEPA sourced payment', function (): void {
    $fixture = sepaPaymentFixture(SepaDebitItemStatus::Returned);

    expect(fn () => app(ReversePaymentForSepaReturn::class)->handle($fixture['item'], 'Rücklastschrift', $fixture['user']))
        ->toThrow(DomainException::class, 'Für diese SEPA-Position wurde keine Zahlung gefunden.');

    $this->assertDatabaseCount('payments', 0);
});

it('does not return an existing SEPA payment to another current club', function (): void {
    $fixture = sepaPaymentFixture();
    $service = app(CreatePaymentFromSettledSepaItem::class);
    $service->handle($fixture['item'], CarbonImmutable::parse('2026-10-08'), $fixture['user']);
    app(CurrentClub::class)->set(Club::factory()->create());

    expect(fn () => $service->handle($fixture['item'], CarbonImmutable::parse('2026-10-08'), $fixture['user']))
        ->toThrow(DomainException::class, 'Die Lastschriftposition gehört nicht zum aktuellen Verein.');

    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseCount('payment_allocations', 1);
});

it('rejects a returned item from another current club without reversing its payment', function (): void {
    $fixture = sepaPaymentFixture();
    $payment = app(CreatePaymentFromSettledSepaItem::class)->handle($fixture['item'], CarbonImmutable::parse('2026-10-08'), $fixture['user']);
    $fixture['item']->update(['status' => 'returned']);
    app(CurrentClub::class)->set(Club::factory()->create());

    expect(fn () => app(ReversePaymentForSepaReturn::class)->handle($fixture['item'], 'Rücklastschrift', $fixture['user']))
        ->toThrow(DomainException::class, 'Die Zahlung gehört nicht zum aktuellen Verein.');

    $this->assertDatabaseHas('payments', ['id' => $payment->getKey(), 'status' => 'booked']);
    $this->assertDatabaseHas('contribution_charges', ['id' => $fixture['item']->contribution_charge_id, 'status' => 'paid']);
});
