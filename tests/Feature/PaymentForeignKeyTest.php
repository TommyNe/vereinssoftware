<?php

use App\Domain\Club\Models\Club;
use App\Domain\Membership\Models\Member;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('stores payments for a member uuid and prevents deleting that member', function (): void {
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $payment = [
        'id' => (string) Str::uuid(),
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'status' => 'booked',
        'method' => 'bank_transfer',
        'amount' => '25.00',
        'booking_date' => '2026-10-08',
    ];

    DB::table('payments')->insert($payment);

    $this->assertDatabaseHas('payments', $payment);
    expect(fn () => DB::table('members')->where('uuid', $member->getKey())->delete())
        ->toThrow(QueryException::class);
});
