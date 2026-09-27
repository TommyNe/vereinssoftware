<?php

use App\Domain\Club\Models\Club;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MembershipType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('preserves documents and allows membership changes after repairing the foreign key', function (): void {
    $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();

    $migration = require database_path('migrations/2026_09_25_195428_fix_member_documents_member_foreign_key.php');
    $migration->down();
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $membershipType = MembershipType::query()->create(['club_id' => $club->getKey(), 'name' => 'Aktiv']);
    $documentId = (string) Str::uuid();
    $document = [
        'id' => $documentId,
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'type' => 'other',
        'original_name' => 'document.pdf',
        'storage_path' => 'documents/document.pdf',
        'mime_type' => 'application/pdf',
        'size' => 123,
    ];
    Schema::withoutForeignKeyConstraints(fn () => DB::table('member_documents')->insert($document));

    $migration->up();
    DB::table('members')->where('uuid', $member->getKey())->update(['membership_type_id' => $membershipType->getKey()]);

    $this->assertDatabaseHas('members', ['uuid' => $member->getKey(), 'membership_type_id' => $membershipType->getKey()]);
    $this->assertDatabaseHas('member_documents', $document);

    DB::table('members')->where('uuid', $member->getKey())->delete();

    $this->assertDatabaseMissing('member_documents', ['id' => $documentId]);
});
