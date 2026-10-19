<?php

use App\Application\Contribution\MemberFinanceSummary;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Membership\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sums only the members booked payment credit', function (): void {
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $otherMember = Member::factory()->create(['club_id' => $club->getKey()]);
    $payment = Payment::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'status' => 'booked',
        'method' => 'bank_transfer',
        'amount' => '12.34',
        'booking_date' => '2026-10-19',
    ]);
    $payment->replicate()->fill(['amount' => '56.78'])->save();
    $payment->replicate()->fill(['amount' => '999.00', 'status' => 'reversed'])->save();
    $payment->replicate()->fill(['amount' => '888.00', 'member_id' => $otherMember->getKey()])->save();

    $credit = app(MemberFinanceSummary::class)->creditAmount($member);

    expect($credit)->toBe('69.12');
});
