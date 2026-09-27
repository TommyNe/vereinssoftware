<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Enums\MemberDocumentType;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MemberDocument;
use App\Filament\Resources\Members\Pages\ViewMember;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

/** @return array{Member, User} */
function memberDocumentPage(bool $canManage = true): array
{
    $club = Club::factory()->create();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    $permissions = [Permission::MembersView, Permission::MembersDocumentsView];
    if ($canManage) {
        $permissions[] = Permission::MembersDocumentsManage;
    }
    foreach ($permissions as $permission) {
        $user->givePermissionTo(SpatiePermission::findOrCreate($permission->value, 'web'));
    }
    test()->actingAs($user);
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    return [$member, $user];
}

test('uploading a document stores its metadata and immediately displays it', function (): void {
    Storage::fake('local');
    [$member, $user] = memberDocumentPage();
    $file = UploadedFile::fake()->createWithContent('Mitgliedsantrag.pdf', "%PDF-1.4\nTest document");

    $page = Livewire::actingAs($user)->test(ViewMember::class, ['record' => $member->getKey()])
        ->assertSee('Keine Dokumente vorhanden')
        ->callAction(TestAction::make('uploadDocument')->schemaComponent('documents'), data: [
            'type' => MemberDocumentType::MembershipApplication->value,
            'document' => $file,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Dokument hochgeladen')
        ->assertSee('Mitgliedsantrag.pdf')
        ->assertSee('application/pdf');

    $document = MemberDocument::query()->sole();
    $this->assertDatabaseHas('member_documents', [
        'id' => $document->getKey(),
        'member_id' => $member->getKey(),
        'club_id' => $member->club_id,
        'original_name' => 'Mitgliedsantrag.pdf',
        'type' => MemberDocumentType::MembershipApplication->value,
        'uploaded_by' => $user->getKey(),
        'size' => $file->getSize(),
    ]);
    Storage::disk('local')->assertExists($document->storage_path);
    $page->call('$refresh')
        ->assertSee('Mitgliedsantrag.pdf')
        ->assertSee(route('member-documents.download', $document));
});

test('a member viewer without document management permission cannot upload', function (): void {
    [$member, $user] = memberDocumentPage(canManage: false);

    Livewire::actingAs($user)->test(ViewMember::class, ['record' => $member->getKey()])
        ->assertActionDoesNotExist(TestAction::make('uploadDocument')->schemaComponent('documents'));

    $this->assertDatabaseCount('member_documents', 0);
});

test('uploading requires a document and its type', function (): void {
    [$member, $user] = memberDocumentPage();

    Livewire::actingAs($user)->test(ViewMember::class, ['record' => $member->getKey()])
        ->callAction(TestAction::make('uploadDocument')->schemaComponent('documents'), data: [])
        ->assertHasActionErrors(['document' => 'required', 'type' => 'required']);

    $this->assertDatabaseCount('member_documents', 0);
});

test('an existing private file cannot be registered by submitting its path', function (): void {
    Storage::fake('local');
    [$member, $user] = memberDocumentPage();
    $path = 'members/another-club/another-member/documents/private.pdf';
    Storage::disk('local')->put($path, "%PDF-1.4\nPrivate document");

    Livewire::actingAs($user)->test(ViewMember::class, ['record' => $member->getKey()])
        ->callAction(TestAction::make('uploadDocument')->schemaComponent('documents'), data: [
            'type' => MemberDocumentType::Other->value,
            'document' => [$path],
        ])
        ->assertHasActionErrors(['document']);

    $this->assertDatabaseCount('member_documents', 0);
    Storage::disk('local')->assertExists($path);
});

test('the member overview displays grouped details and empty relationship states', function (): void {
    [$member, $user] = memberDocumentPage();

    Livewire::actingAs($user)->test(ViewMember::class, ['record' => $member->getKey()])
        ->assertSeeInOrder(['Mitgliedschaft', 'Persönliche Daten', 'Kontakt', 'Anschrift', 'Abteilungen', 'Vereinsfunktionen', 'Dokumente', 'Funktionshistorie', 'Systeminformationen'])
        ->assertSee($member->first_name)
        ->assertSee($member->last_name)
        ->assertSee($member->member_number)
        ->assertSee($member->birth_date->format('d.m.Y'))
        ->assertSee('Keine Abteilungen zugeordnet')
        ->assertSee('Keine aktiven Funktionen')
        ->assertSee('Keine Dokumente vorhanden');
});

test('member actions open from their corresponding info cards', function (string $section, string $action, Permission $permission): void {
    [$member, $user] = memberDocumentPage();
    $user->givePermissionTo(SpatiePermission::findOrCreate($permission->value, 'web'));

    Livewire::actingAs($user)->test(ViewMember::class, ['record' => $member->getKey()])
        ->assertActionVisible(TestAction::make($action)->schemaComponent($section))
        ->mountAction(TestAction::make($action)->schemaComponent($section))
        ->assertHasNoActionErrors();
})->with([
    'personal details' => ['personal', 'changePersonalData', Permission::MembersUpdate],
    'contact' => ['contact', 'changeContactData', Permission::MembersUpdate],
    'address' => ['address', 'changeAddress', Permission::MembersUpdate],
    'membership' => ['membership', 'changeMembershipType', Permission::MembersMembershipManage],
    'departments' => ['departments', 'joinDepartment', Permission::MembersDepartmentsManage],
    'functions' => ['functions', 'assignFunction', Permission::MembersFunctionsManage],
]);
