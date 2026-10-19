<?php

use App\Application\Club\CurrentClub;
use App\Application\Contribution\FinanceOverviewService;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ChargePaymentState;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Membership\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 10, 19)->startOfDay());
});

function financeOverviewMember(): Member
{
    $club = Club::factory()->create();
    app(CurrentClub::class)->set($club);

    return Member::factory()->create(['club_id' => $club->getKey()]);
}

function financeOverviewCharge(
    Member $member,
    string $amount = '120.00',
    string $dueDate = '2026-11-01',
    ContributionChargeStatus $status = ContributionChargeStatus::Open,
): ContributionCharge {
    $type = ContributionType::query()->firstOrCreate([
        'club_id' => $member->club_id,
        'name' => 'Jahresbeitrag',
    ], ['interval' => 'yearly']);

    return ContributionCharge::query()->create([
        'club_id' => $member->club_id,
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'amount' => $amount,
        'status' => $status,
        'description' => 'Jahresbeitrag 2026',
        'period_from' => '2026-01-01',
        'due_date' => $dueDate,
    ]);
}

function financeOverviewPayment(
    Member $member,
    string $amount = '150.00',
    PaymentStatus $status = PaymentStatus::Booked,
): Payment {
    return Payment::query()->create([
        'club_id' => $member->club_id,
        'member_id' => $member->getKey(),
        'amount' => $amount,
        'status' => $status,
        'method' => 'bank_transfer',
        'booking_date' => '2026-10-18',
    ]);
}

function financeOverviewAllocation(Payment $payment, ContributionCharge $charge, string $amount): void
{
    $payment->allocations()->create([
        'club_id' => $payment->club_id,
        'contribution_charge_id' => $charge->getKey(),
        'amount' => $amount,
    ]);
}

it('returns zero totals and counts for a club without financial records', function (): void {
    financeOverviewMember();

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->totalOpen)->toBe('0.00');
    expect($overview->totalOverdue)->toBe('0.00');
    expect($overview->totalPartiallyPaid)->toBe('0.00');
    expect($overview->totalUnallocatedPayments)->toBe('0.00');
    expect($overview->openChargesCount)->toBe(0);
    expect($overview->overdueChargesCount)->toBe(0);
    expect($overview->partiallyPaidChargesCount)->toBe(0);
    expect($overview->unallocatedPaymentsCount)->toBe(0);
    expect($overview->openCharges)->toBe([]);
    expect($overview->unallocatedPayments)->toBe([]);
});

it('keeps a foreign clubs million euro charge out of the current clubs hundred euro overview', function (): void {
    $foreignClub = Club::factory()->create();
    $foreignMember = Member::factory()->create(['club_id' => $foreignClub->getKey()]);
    $foreignCharge = financeOverviewCharge($foreignMember, amount: '1000000.00', dueDate: '2026-10-01');
    $foreignPayment = financeOverviewPayment($foreignMember, amount: '888.00');
    $member = financeOverviewMember();
    $charge = financeOverviewCharge($member, amount: '100.00', dueDate: '2026-10-01');
    $payment = financeOverviewPayment($member);

    $service = app(FinanceOverviewService::class);
    $overview = $service->getOverview();

    expect($overview->totalOpen)->toBe('100.00');
    expect($overview->totalOverdue)->toBe('100.00');
    expect($overview->totalUnallocatedPayments)->toBe('150.00');
    expect($overview->openChargesCount)->toBe(1);
    expect($overview->overdueChargesCount)->toBe(1);
    expect($overview->unallocatedPaymentsCount)->toBe(1);
    expect(array_column($overview->openCharges, 'chargeId'))->toBe([(string) $charge->getKey()]);
    expect(array_column($overview->unallocatedPayments, 'paymentId'))->toBe([(string) $payment->getKey()]);

    app(CurrentClub::class)->set($foreignClub);

    $foreignOverview = $service->getOverview();

    expect($foreignOverview->totalOpen)->toBe('1000000.00');
    expect($foreignOverview->totalOverdue)->toBe('1000000.00');
    expect($foreignOverview->totalUnallocatedPayments)->toBe('888.00');
    expect($foreignOverview->openChargesCount)->toBe(1);
    expect($foreignOverview->overdueChargesCount)->toBe(1);
    expect($foreignOverview->unallocatedPaymentsCount)->toBe(1);
    expect(array_column($foreignOverview->openCharges, 'chargeId'))->toBe([(string) $foreignCharge->getKey()]);
    expect(array_column($foreignOverview->unallocatedPayments, 'paymentId'))->toBe([(string) $foreignPayment->getKey()]);
});

it('counts an unpaid charge and returns its full outstanding amount', function (): void {
    $member = financeOverviewMember();
    $charge = financeOverviewCharge($member);

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->totalOpen)->toBe('120.00');
    expect($overview->openChargesCount)->toBe(1);
    expect($overview->openCharges)->toHaveCount(1);
    expect($overview->openCharges[0]->chargeId)->toBe((string) $charge->getKey());
    expect($overview->openCharges[0]->paidAmount)->toBe('0.00');
    expect($overview->openCharges[0]->outstandingAmount)->toBe('120.00');
    expect($overview->openCharges[0]->paymentState)->toBe(ChargePaymentState::Open);
});

it('excludes a fully paid charge from outstanding totals and counts', function (): void {
    $member = financeOverviewMember();
    $charge = financeOverviewCharge($member, status: ContributionChargeStatus::Paid);
    $payment = financeOverviewPayment($member, amount: '120.00');
    financeOverviewAllocation($payment, $charge, '120.00');

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->totalOpen)->toBe('0.00');
    expect($overview->openChargesCount)->toBe(0);
    expect($overview->openCharges)->toBe([]);
});

it('counts only the outstanding balance of a partially paid charge', function (): void {
    $member = financeOverviewMember();
    $charge = financeOverviewCharge($member, amount: '100.00', dueDate: '2026-10-01');
    $payment = financeOverviewPayment($member, amount: '50.00');
    financeOverviewAllocation($payment, $charge, '50.00');

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->totalOpen)->toBe('50.00');
    expect($overview->totalPartiallyPaid)->toBe('50.00');
    expect($overview->totalOverdue)->toBe('50.00');
    expect($overview->openChargesCount)->toBe(1);
    expect($overview->partiallyPaidChargesCount)->toBe(1);
    expect($overview->overdueChargesCount)->toBe(1);
    expect($overview->openCharges[0]->paidAmount)->toBe('50.00');
    expect($overview->openCharges[0]->outstandingAmount)->toBe('50.00');
    expect($overview->openCharges[0]->paymentState)->toBe(ChargePaymentState::PartiallyPaid);
});

it('classifies overdue and future charges by their due date', function (string $dueDate, bool $overdue, string $totalOverdue, int $overdueCount): void {
    $member = financeOverviewMember();
    financeOverviewCharge($member, dueDate: $dueDate);

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->openChargesCount)->toBe(1);
    expect($overview->openCharges[0]->overdue)->toBe($overdue);
    expect($overview->totalOverdue)->toBe($totalOverdue);
    expect($overview->overdueChargesCount)->toBe($overdueCount);
})->with([
    'past due' => ['2026-10-18', true, '120.00', 1],
    'future due' => ['2026-10-20', false, '0.00', 0],
]);

it('ignores cancelled charges even when they would otherwise be overdue', function (): void {
    $member = financeOverviewMember();
    financeOverviewCharge($member, dueDate: '2026-10-01', status: ContributionChargeStatus::Cancelled);

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->totalOpen)->toBe('0.00');
    expect($overview->totalOverdue)->toBe('0.00');
    expect($overview->openChargesCount)->toBe(0);
    expect($overview->overdueChargesCount)->toBe(0);
    expect($overview->openCharges)->toBe([]);
});

it('recognizes a booked payment without allocations', function (): void {
    $member = financeOverviewMember();
    $payment = financeOverviewPayment($member);

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->totalUnallocatedPayments)->toBe('150.00');
    expect($overview->unallocatedPaymentsCount)->toBe(1);
    expect($overview->unallocatedPayments)->toHaveCount(1);
    expect($overview->unallocatedPayments[0]->paymentId)->toBe((string) $payment->getKey());
    expect($overview->unallocatedPayments[0]->allocatedAmount)->toBe('0.00');
    expect($overview->unallocatedPayments[0]->unallocatedAmount)->toBe('150.00');
});

it('ignores fully allocated payments', function (): void {
    $member = financeOverviewMember();
    $charge = financeOverviewCharge($member);
    $payment = financeOverviewPayment($member, amount: '120.00');
    financeOverviewAllocation($payment, $charge, '120.00');

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->totalUnallocatedPayments)->toBe('0.00');
    expect($overview->unallocatedPaymentsCount)->toBe(0);
    expect($overview->unallocatedPayments)->toBe([]);
});

it('ignores reversed payments and their allocations when calculating outstanding charges', function (): void {
    $member = financeOverviewMember();
    $charge = financeOverviewCharge($member);
    $payment = financeOverviewPayment($member, status: PaymentStatus::Reversed);
    financeOverviewAllocation($payment, $charge, '120.00');

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->totalUnallocatedPayments)->toBe('0.00');
    expect($overview->unallocatedPaymentsCount)->toBe(0);
    expect($overview->unallocatedPayments)->toBe([]);
    expect($overview->totalOpen)->toBe('120.00');
    expect($overview->openChargesCount)->toBe(1);
    expect($overview->openCharges[0]->paidAmount)->toBe('0.00');
    expect($overview->openCharges[0]->paymentState)->toBe(ChargePaymentState::Open);
});

it('returns the remaining payment balance after a partial allocation', function (): void {
    $member = financeOverviewMember();
    $charge = financeOverviewCharge($member);
    $payment = financeOverviewPayment($member);
    financeOverviewAllocation($payment, $charge, '120.00');

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->totalUnallocatedPayments)->toBe('30.00');
    expect($overview->unallocatedPaymentsCount)->toBe(1);
    expect($overview->unallocatedPayments[0]->amount)->toBe('150.00');
    expect($overview->unallocatedPayments[0]->allocatedAmount)->toBe('120.00');
    expect($overview->unallocatedPayments[0]->unallocatedAmount)->toBe('30.00');
});

it('calculates the example totals and all counts across mixed charges and payments', function (): void {
    $member = financeOverviewMember();
    $chargeA = financeOverviewCharge($member, dueDate: '2026-10-01');
    $chargeB = financeOverviewCharge($member, amount: '100.00');
    $chargeC = financeOverviewCharge($member, amount: '80.00', status: ContributionChargeStatus::Paid);
    $settledCharge = financeOverviewCharge($member, status: ContributionChargeStatus::Paid);
    $paymentB = financeOverviewPayment($member, amount: '50.00');
    $paymentC = financeOverviewPayment($member, amount: '80.00');
    $paymentD = financeOverviewPayment($member);
    financeOverviewAllocation($paymentB, $chargeB, '50.00');
    financeOverviewAllocation($paymentC, $chargeC, '80.00');
    financeOverviewAllocation($paymentD, $settledCharge, '120.00');

    $overview = app(FinanceOverviewService::class)->getOverview();

    expect($overview->totalOpen)->toBe('170.00');
    expect($overview->totalOverdue)->toBe('120.00');
    expect($overview->totalPartiallyPaid)->toBe('50.00');
    expect($overview->totalUnallocatedPayments)->toBe('30.00');
    expect($overview->openChargesCount)->toBe(2);
    expect($overview->overdueChargesCount)->toBe(1);
    expect($overview->partiallyPaidChargesCount)->toBe(1);
    expect($overview->unallocatedPaymentsCount)->toBe(1);
    expect(array_column($overview->openCharges, 'chargeId'))->toBe([(string) $chargeA->getKey(), (string) $chargeB->getKey()]);
    expect(array_column($overview->unallocatedPayments, 'paymentId'))->toBe([(string) $paymentD->getKey()]);
});
