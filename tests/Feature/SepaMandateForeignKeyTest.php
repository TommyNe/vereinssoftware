<?php

use App\Domain\Club\Models\Club;
use App\Domain\Membership\Models\Member;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * @return array<string, string>
 */
function sepaMandateForeignKeyAttributes(Club $club, string $memberId): array
{
    return [
        'id' => (string) Str::uuid(),
        'club_id' => (string) $club->getKey(),
        'member_id' => $memberId,
        'status' => 'active',
        'mandate_reference' => 'MANDATE-001',
        'account_holder' => 'Max Mustermann',
        'iban' => 'encrypted-iban',
        'signed_at' => '2026-10-01',
    ];
}

it('stores a mandate for a member uuid and deletes it when the member is deleted', function (): void {
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $mandate = sepaMandateForeignKeyAttributes($club, (string) $member->getKey());

    DB::table('sepa_mandates')->insert($mandate);

    $this->assertDatabaseHas('sepa_mandates', $mandate);

    DB::table('members')->where('uuid', $member->getKey())->delete();

    $this->assertDatabaseMissing('sepa_mandates', ['id' => $mandate['id']]);
});

it('rejects a mandate referencing a nonexistent member', function (): void {
    $club = Club::factory()->create();
    $mandate = sepaMandateForeignKeyAttributes($club, (string) Str::uuid());

    expect(fn () => DB::table('sepa_mandates')->insert($mandate))
        ->toThrow(QueryException::class);

    $this->assertDatabaseCount('sepa_mandates', 0);
});
