<?php

use App\Domain\Club\Models\Club;
use App\Domain\Membership\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('cascades member document deletion through the member uuid foreign key', function (): void {
    $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();

    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
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
    DB::table('member_documents')->insert($document);

    $this->assertDatabaseHas('member_documents', $document);

    DB::table('members')->where('uuid', $member->getKey())->delete();

    $this->assertDatabaseMissing('member_documents', ['id' => $documentId]);
});
