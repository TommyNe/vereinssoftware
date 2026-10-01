<?php

use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MemberDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

/** @return array{MemberDocument, User} */
function downloadableMemberDocument(bool $canView = true, bool $belongsToClub = true): array
{
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $user = User::factory()->create();
    if ($belongsToClub) {
        $user->clubs()->attach($club);
    }
    setPermissionsTeamId($club->getKey());
    if ($canView) {
        $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::MembersDocumentsView->value, 'web'));
    }
    $document = MemberDocument::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'type' => 'other',
        'original_name' => 'Dokument.pdf',
        'storage_path' => 'members/'.$club->getKey().'/'.$member->getKey().'/documents/document.pdf',
        'mime_type' => 'application/pdf',
        'size' => 13,
    ]);

    return [$document, $user];
}

test('authorized users download the original file from its club regardless of the selected club', function (): void {
    Storage::fake('local');
    [$document, $user] = downloadableMemberDocument();
    $otherClub = Club::factory()->create();
    $user->clubs()->attach($otherClub);
    Storage::disk('local')->put($document->storage_path, 'Document body');
    setPermissionsTeamId($otherClub->getKey());

    $this->actingAs($user)->withSession(['current_club_id' => $otherClub->getKey()])
        ->get(route('member-documents.download', $document))
        ->assertDownload('Dokument.pdf')
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertStreamedContent('Document body');

    $activity = Activity::query()->where('event', AuditAction::DocumentDownloaded->value)->sole();
    expect($activity->causer_id)->toBe($user->getKey())
        ->and((string) $activity->subject_id)->toBe((string) $document->getKey())
        ->and($activity->getProperty('club_id'))->toBe($document->club_id);
});

test('document downloads require authentication', function (): void {
    [$document] = downloadableMemberDocument();

    $this->getJson(route('member-documents.download', $document))->assertUnauthorized();
});

test('document downloads return 403 without document viewing permission', function (): void {
    [$document, $user] = downloadableMemberDocument(canView: false);

    $this->actingAs($user)->get(route('member-documents.download', $document))->assertForbidden();

    expect(Activity::query()->where('event', AuditAction::DocumentDownloaded->value)->exists())->toBeFalse();
});

test('document downloads return 404 for another clubs documents', function (): void {
    [$document, $user] = downloadableMemberDocument(belongsToClub: false);

    $this->actingAs($user)->get(route('member-documents.download', $document))->assertNotFound();

    expect(Activity::query()->where('event', AuditAction::DocumentDownloaded->value)->exists())->toBeFalse();
});

test('document downloads return 404 when the stored file is missing', function (): void {
    Storage::fake('local');
    [$document, $user] = downloadableMemberDocument();

    $this->actingAs($user)->get(route('member-documents.download', $document))->assertNotFound();
});
