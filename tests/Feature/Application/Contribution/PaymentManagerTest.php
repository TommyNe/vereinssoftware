<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\PaymentBalanceResolver;
use App\Application\Contribution\PaymentManager;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ChargePaymentState;
use App\Domain\Contribution\Enums\PaymentMethod;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Contribution\Models\PaymentAllocation;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('rejects zero and negative allocation amounts in the manager', function (string $amount): void {
    $fixture = paymentAllocationFixture();

    expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], $amount))
        ->toThrow(DomainException::class, 'Der Zuordnungsbetrag muss größer als 0 sein.');

    $this->assertDatabaseCount('payment_allocations', 0);
})->with(['zero' => '0.00', 'negative' => '-0.01']);

it('creates a payment in the current club and casts its payment method', function (PaymentMethod $method): void {
    $fixture = paymentAllocationFixture();
    $user = User::factory()->create();

    $payment = createAuditedTestPayment($fixture, $user, method: $method);

    $this->assertDatabaseHas('payments', [
        'id' => $payment->getKey(),
        'club_id' => app(CurrentClub::class)->id(),
        'member_id' => $fixture['payment']->member_id,
        'amount' => '100.00',
        'method' => $method->value,
        'status' => 'booked',
        'created_by' => $user->getKey(),
    ]);
    expect($payment->fresh()->method)->toBe($method);
})->with(PaymentMethod::cases());

it('rejects payment creation for a member of another club', function (): void {
    $fixture = paymentAllocationFixture();
    $user = User::factory()->create();
    $foreignMember = Member::factory()->create(['club_id' => Club::factory()->create()->getKey()]);

    expect(fn () => createAuditedTestPayment($fixture, $user, member: $foreignMember))
        ->toThrow(DomainException::class, 'Das Mitglied gehört nicht zum aktuellen Verein.');

    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::PaymentCreated->value]);
});

it('rejects zero and negative payment amounts', function (string $amount): void {
    $fixture = paymentAllocationFixture();
    $user = User::factory()->create();

    expect(fn () => createAuditedTestPayment($fixture, $user, amount: $amount))
        ->toThrow(DomainException::class, 'Der Zahlungsbetrag muss größer als 0 sein.');

    $this->assertDatabaseCount('payments', 1);
})->with(['zero' => '0.00', 'negative' => '-0.01']);

it('rejects allocations between different members', function (): void {
    $fixture = paymentAllocationFixture();
    $otherMember = Member::factory()->create(['club_id' => $fixture['payment']->club_id]);
    $fixture['charge']->update(['member_id' => $otherMember->getKey()]);

    expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '25.00'))
        ->toThrow(DomainException::class, 'Zahlung und Forderung gehören nicht zum selben Mitglied.');

    $this->assertDatabaseCount('payment_allocations', 0);
});

it('rejects foreign payments and foreign charges when allocating', function (string $foreignRecord): void {
    $fixture = paymentAllocationFixture();
    $fixture[$foreignRecord]->update(['club_id' => Club::factory()->create()->getKey()]);

    expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '25.00'))
        ->toThrow(DomainException::class, 'Zahlung und Forderung müssen zum aktuellen Verein gehören.');

    $this->assertDatabaseCount('payment_allocations', 0);
})->with(['foreign payment' => 'payment', 'foreign charge' => 'charge']);

it('rejects allocation when both records belong to another current club', function (): void {
    $fixture = paymentAllocationFixture();
    app(CurrentClub::class)->set(Club::factory()->create());

    expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '25.00'))
        ->toThrow(DomainException::class, 'Zahlung und Forderung müssen zum aktuellen Verein gehören.');

    $this->assertDatabaseCount('payment_allocations', 0);
});

it('calculates balances and payment state from booked allocations only', function (string $allocatedAmount, bool $reverse, string $paidAmount, string $outstandingAmount, ChargePaymentState $state): void {
    $fixture = paymentAllocationFixture();
    if ($allocatedAmount === '0.00') {
        $fixture['payment']->delete();
    } else {
        app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], $allocatedAmount);
    }
    if ($reverse) {
        app(PaymentManager::class)->reverse($fixture['payment'], 'Rückbuchung', User::factory()->create());
    }

    $resolver = app(PaymentBalanceResolver::class);
    $charge = $fixture['charge']->fresh();

    expect($resolver->allocatedAmount($charge))->toBe($paidAmount);
    expect($resolver->outstandingAmount($charge))->toBe($outstandingAmount);
    expect($resolver->state($charge))->toBe($state);
})->with([
    'no payment' => ['0.00', false, '0.00', '100.00', ChargePaymentState::Open],
    'partial allocation' => ['40.00', false, '40.00', '60.00', ChargePaymentState::PartiallyPaid],
    'full allocation' => ['100.00', false, '100.00', '0.00', ChargePaymentState::Paid],
    'reversed allocation' => ['100.00', true, '0.00', '100.00', ChargePaymentState::Open],
]);

it('keeps 30 euros unallocated after paying a 120 euro charge with a 150 euro payment', function (): void {
    $fixture = paymentAllocationFixture('150.00', '120.00');

    app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '120.00');

    $resolver = app(PaymentBalanceResolver::class);
    expect($resolver->allocatedAmount($fixture['charge']->fresh()))->toBe('120.00');
    expect($resolver->outstandingAmount($fixture['charge']->fresh()))->toBe('0.00');
    expect($resolver->unallocatedAmount($fixture['payment']->fresh()))->toBe('30.00');
    $this->assertDatabaseHas('payment_allocations', ['payment_id' => $fixture['payment']->getKey(), 'amount' => '120.00']);
});

it('rejects allocating all 150 euros to a 120 euro charge', function (): void {
    $fixture = paymentAllocationFixture('150.00', '120.00');

    expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '150.00'))
        ->toThrow(DomainException::class, 'Die Zuordnung ist größer als der offene Forderungsbetrag.');

    $this->assertDatabaseCount('payment_allocations', 0);
    expect(app(PaymentBalanceResolver::class)->unallocatedAmount($fixture['payment']))->toBe('150.00');
});

/** @param array{payment: Payment} $fixture */
function createAuditedTestPayment(array $fixture, User $user, string $amount = '100.00', ?Member $member = null, PaymentMethod $method = PaymentMethod::BankTransfer): Payment
{
    return app(PaymentManager::class)->create(
        member: $member ?? $fixture['payment']->member,
        amount: $amount,
        method: $method,
        bookingDate: CarbonImmutable::parse('2026-10-08'),
        valueDate: null,
        reference: 'Privater Verwendungszweck',
        notes: 'Vertrauliche Bankdaten',
        createdBy: $user,
    );
}

it('audits payment creation allocation and reversal with only the requested financial properties', function (): void {
    $fixture = paymentAllocationFixture();
    $user = User::factory()->create();
    $this->actingAs($user);

    $payment = createAuditedTestPayment($fixture, $user);
    $allocation = app(PaymentManager::class)->allocate($payment, $fixture['charge'], '25.00');
    app(PaymentManager::class)->reverse($payment, 'Vertrauliche Rückbuchungsdetails', $user);

    foreach ([AuditAction::PaymentCreated, AuditAction::PaymentReversed] as $event) {
        $activity = Activity::query()->where('event', $event->value)->sole();
        expect((string) $activity->subject_id)->toBe($payment->getKey());
        expect($activity->subject_type)->toBe($payment->getMorphClass());
        expect($activity->causer_id)->toBe($user->getKey());
        expect($activity->properties->except(['club_id', 'ip_address', 'user_agent', 'path'])->all())
            ->toEqual([
                'payment_id' => $payment->getKey(),
                'member_id' => $payment->member_id,
                'amount' => '100.00',
                'method' => 'bank_transfer',
            ]);
    }
    $allocated = Activity::query()->where('event', AuditAction::PaymentAllocated->value)->sole();
    expect((string) $allocated->subject_id)->toBe($allocation->getKey());
    expect($allocated->subject_type)->toBe($allocation->getMorphClass());
    expect($allocated->causer_id)->toBe($user->getKey());
    expect($allocated->properties->except(['club_id', 'ip_address', 'user_agent', 'method', 'path'])->all())
        ->toEqual([
            'payment_id' => $payment->getKey(),
            'charge_id' => $fixture['charge']->getKey(),
            'amount' => '25.00',
        ]);
});

it('rolls back payment creation if its audit entry cannot be saved', function (): void {
    $fixture = paymentAllocationFixture();
    $user = User::factory()->create();
    Activity::creating(function (): void {
        throw new RuntimeException('Audit failed.');
    });

    try {
        expect(fn () => createAuditedTestPayment($fixture, $user))->toThrow(RuntimeException::class, 'Audit failed.');
    } finally {
        Activity::flushEventListeners();
    }

    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::PaymentCreated->value]);
});

it('rolls back allocation and synchronized charge status if the audit entry cannot be saved', function (): void {
    $fixture = paymentAllocationFixture();
    Activity::creating(function (): void {
        throw new RuntimeException('Audit failed.');
    });

    try {
        expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '100.00'))
            ->toThrow(RuntimeException::class, 'Audit failed.');
    } finally {
        Activity::flushEventListeners();
    }

    $this->assertDatabaseCount('payment_allocations', 0);
    $this->assertDatabaseHas('contribution_charges', ['id' => $fixture['charge']->getKey(), 'status' => 'open', 'paid_at' => null]);
    $this->assertDatabaseMissing('activity_log', ['event' => AuditAction::PaymentAllocated->value]);
});

it('rolls back reversal and charge synchronization if the audit entry cannot be saved', function (): void {
    $fixture = paymentAllocationFixture();
    $user = User::factory()->create();
    app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '100.00');
    Activity::creating(function (): void {
        throw new RuntimeException('Audit failed.');
    });

    try {
        expect(fn () => app(PaymentManager::class)->reverse($fixture['payment'], 'Rückbuchung', $user))
            ->toThrow(RuntimeException::class, 'Audit failed.');
    } finally {
        Activity::flushEventListeners();
    }

    $this->assertDatabaseHas('payments', ['id' => $fixture['payment']->getKey(), 'status' => 'booked', 'reversed_at' => null]);
    $this->assertDatabaseHas('contribution_charges', ['id' => $fixture['charge']->getKey(), 'status' => 'paid']);
    expect(Activity::query()->where('event', AuditAction::PaymentReversed->value)->exists())->toBeFalse();
});

it('reopens every charge funded by a reversed payment and clears paid_at', function (): void {
    $fixture = paymentAllocationFixture('200.00');
    $secondCharge = $fixture['charge']->replicate();
    $secondCharge->save();
    $user = User::factory()->create();
    app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '100.00');
    app(PaymentManager::class)->allocate($fixture['payment'], $secondCharge, '100.00');

    app(PaymentManager::class)->reverse($fixture['payment'], ' Rückbuchung ', $user);

    foreach ([$fixture['charge'], $secondCharge] as $charge) {
        $this->assertDatabaseHas('contribution_charges', [
            'id' => $charge->getKey(),
            'status' => 'open',
            'paid_at' => null,
        ]);
    }
    $this->assertDatabaseHas('payments', [
        'id' => $fixture['payment']->getKey(),
        'status' => 'reversed',
        'reversal_reason' => 'Rückbuchung',
        'reversed_by' => $user->getKey(),
    ]);
    $this->assertDatabaseCount('payment_allocations', 2);
});

it('reopens a charge as partially paid when another booked payment remains', function (): void {
    $fixture = paymentAllocationFixture();
    $otherPayment = $fixture['payment']->replicate();
    $otherPayment->save();
    $user = User::factory()->create();
    app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '50.00');
    app(PaymentManager::class)->allocate($otherPayment, $fixture['charge'], '50.00');

    app(PaymentManager::class)->reverse($fixture['payment'], 'Rückbuchung', $user);

    $this->assertDatabaseHas('contribution_charges', [
        'id' => $fixture['charge']->getKey(),
        'status' => 'open',
        'paid_at' => null,
    ]);
    expect(app(PaymentBalanceResolver::class)->outstandingAmount($fixture['charge']->fresh()))
        ->toBe('50.00');
});

it('preserves the cancelled charge status after payment reversal', function (): void {
    $fixture = paymentAllocationFixture();
    $user = User::factory()->create();
    app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '100.00');
    $fixture['charge']->update(['status' => 'cancelled']);

    app(PaymentManager::class)->reverse($fixture['payment'], 'Rückbuchung', $user);

    $this->assertDatabaseHas('contribution_charges', [
        'id' => $fixture['charge']->getKey(),
        'status' => 'cancelled',
    ]);
});

it('rolls back the reversal and charge changes if synchronization fails', function (): void {
    $fixture = paymentAllocationFixture();
    $user = User::factory()->create();
    app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '100.00');
    ContributionCharge::updated(function (): void {
        throw new RuntimeException('Reversal synchronization failed.');
    });

    try {
        expect(fn () => app(PaymentManager::class)->reverse($fixture['payment'], 'Rückbuchung', $user))
            ->toThrow(RuntimeException::class, 'Reversal synchronization failed.');
    } finally {
        ContributionCharge::flushEventListeners();
    }

    $this->assertDatabaseHas('payments', [
        'id' => $fixture['payment']->getKey(),
        'status' => 'booked',
        'reversed_at' => null,
        'reversed_by' => null,
    ]);
    $this->assertDatabaseHas('contribution_charges', [
        'id' => $fixture['charge']->getKey(),
        'status' => 'paid',
    ]);
});

it('synchronizes the charge status after allocating a payment', function (string $amount, string $status, ?string $paidAt): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-08 20:00:00'));
    $fixture = paymentAllocationFixture();
    $previousPayment = $fixture['payment']->replicate();
    $previousPayment->save();
    app(PaymentManager::class)->allocate($previousPayment, $fixture['charge'], '40.00');

    $allocation = app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], $amount);

    $this->assertModelExists($allocation);
    $this->assertDatabaseHas('contribution_charges', [
        'id' => $fixture['charge']->getKey(),
        'status' => $status,
        'paid_at' => $paidAt,
    ]);
})->with([
    'partial payment' => ['10.00', 'open', null],
    'full payment' => ['60.00', 'paid', '2026-10-08 20:00:00'],
]);

it('rolls back both the allocation and charge status when synchronization fails', function (): void {
    $fixture = paymentAllocationFixture();
    ContributionCharge::updated(function (): void {
        throw new RuntimeException('Status synchronization failed.');
    });

    try {
        expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '100.00'))
            ->toThrow(RuntimeException::class, 'Status synchronization failed.');
    } finally {
        ContributionCharge::flushEventListeners();
    }

    $this->assertDatabaseCount('payment_allocations', 0);
    $this->assertDatabaseHas('contribution_charges', [
        'id' => $fixture['charge']->getKey(),
        'status' => 'open',
        'paid_at' => null,
    ]);
});

it('rejects a charge cancelled after the model was loaded', function (): void {
    $fixture = paymentAllocationFixture();
    ContributionCharge::query()->whereKey($fixture['charge']->getKey())->update(['status' => 'cancelled']);

    expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '20.00'))
        ->toThrow(DomainException::class, 'Stornierte Forderungen können keiner Zahlung zugeordnet werden.');

    $this->assertDatabaseCount('payment_allocations', 0);
});

/**
 * @return array{payment: Payment, charge: ContributionCharge}
 */
function paymentAllocationFixture(string $paymentAmount = '100.00', string $chargeAmount = '100.00'): array
{
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
    ]);

    return [
        'payment' => Payment::query()->create([
            'club_id' => $club->getKey(),
            'member_id' => $member->getKey(),
            'status' => 'booked',
            'method' => 'bank_transfer',
            'amount' => $paymentAmount,
            'booking_date' => '2026-10-08',
        ]),
        'charge' => ContributionCharge::query()->create([
            'club_id' => $club->getKey(),
            'member_id' => $member->getKey(),
            'contribution_type_id' => $type->getKey(),
            'status' => 'open',
            'amount' => $chargeAmount,
            'description' => 'Jahresbeitrag 2026',
            'period_from' => '2026-01-01',
            'due_date' => '2026-10-08',
        ]),
    ];
}

it('creates an allocation with the requested amount', function (): void {
    $fixture = paymentAllocationFixture();

    $allocation = app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '25.00');

    $this->assertDatabaseHas('payment_allocations', [
        'id' => $allocation->getKey(),
        'club_id' => $fixture['payment']->club_id,
        'payment_id' => $fixture['payment']->getKey(),
        'contribution_charge_id' => $fixture['charge']->getKey(),
        'amount' => '25.00',
    ]);
});

it('rejects an allocation exceeding the remaining balance', function (string $paymentAmount, string $chargeAmount, string $message): void {
    $fixture = paymentAllocationFixture($paymentAmount, $chargeAmount);
    app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '20.00');

    expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '10.00'))
        ->toThrow(DomainException::class, $message);

    $this->assertDatabaseCount('payment_allocations', 1);
})->with([
    'payment balance' => ['25.00', '100.00', 'Die Zuordnung ist größer als der noch verfügbare Zahlungsbetrag.'],
    'charge balance' => ['100.00', '25.00', 'Die Zuordnung ist größer als der offene Forderungsbetrag.'],
]);

it('checks the persisted amount instead of a stale model', function (string $model, string $message): void {
    $fixture = paymentAllocationFixture();
    $fixture[$model]->newQuery()->whereKey($fixture[$model]->getKey())->update(['amount' => '15.00']);

    expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '20.00'))
        ->toThrow(DomainException::class, $message);

    $this->assertDatabaseCount('payment_allocations', 0);
})->with([
    'payment amount' => ['payment', 'Die Zuordnung ist größer als der noch verfügbare Zahlungsbetrag.'],
    'charge amount' => ['charge', 'Die Zuordnung ist größer als der offene Forderungsbetrag.'],
]);

it('rejects a payment reversed after the model was loaded', function (): void {
    $fixture = paymentAllocationFixture();
    Payment::query()->whereKey($fixture['payment']->getKey())->update(['status' => 'reversed']);

    expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '20.00'))
        ->toThrow(DomainException::class, 'Nur aktive Zahlungen können zugeordnet werden.');

    $this->assertDatabaseCount('payment_allocations', 0);
});

it('rolls back the allocation when saving fails after insertion', function (): void {
    $fixture = paymentAllocationFixture();
    PaymentAllocation::created(function (): void {
        throw new RuntimeException('Allocation failed.');
    });

    try {
        expect(fn () => app(PaymentManager::class)->allocate($fixture['payment'], $fixture['charge'], '20.00'))
            ->toThrow(RuntimeException::class, 'Allocation failed.');
    } finally {
        PaymentAllocation::flushEventListeners();
    }

    $this->assertDatabaseCount('payment_allocations', 0);
});
