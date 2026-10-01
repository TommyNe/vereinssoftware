<?php

use App\Application\Club\ClubInvitationManager;
use App\Application\Club\ClubUserManager;
use App\Application\Club\CurrentClub;
use App\Application\Contribution\MemberContributionOverrideManager;
use App\Application\Membership\Commands\ChangeMemberAddress;
use App\Application\Membership\Handlers\ChangeMemberAddressHandler;
use App\Application\Membership\MemberDocumentManager;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\MemberContributionOverrideType;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Enums\MemberDocumentType;
use App\Domain\Membership\Events\MemberAddressChanged;
use App\Domain\Membership\Models\ClubFunction;
use App\Domain\Membership\Models\Department;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MembershipType;
use App\Domain\Membership\Reactors\MemberAuditReactor;
use App\Domain\Membership\ValueObjects\MemberNumber;
use App\Filament\Pages\Profile;
use App\Infrastructure\EventSourcing\StoredEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

/**
 * @return array{Club, User}
 */
function auditCoverageContext(): array
{
    $club = Club::factory()->create();
    $actor = User::factory()->create();
    app(CurrentClub::class)->set($club);
    test()->actingAs($actor);

    return [$club, $actor];
}

it('audits creation changes and deletion of master data', function (string $modelClass, string $prefix, array $attributes): void {
    [$club, $actor] = auditCoverageContext();

    $record = $modelClass::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Original',
        ...$attributes,
    ]);
    $record->update(['name' => 'Geändert']);
    $record->delete();

    $activities = Activity::query()->where('subject_id', $record->getKey())->orderBy('id')->get();
    expect($activities->pluck('event')->all())->toBe([$prefix.'.created', $prefix.'.updated', $prefix.'.deleted']);
    expect($activities[1]->getProperty('old.name'))->toBe('Original')
        ->and($activities[1]->getProperty('new.name'))->toBe('Geändert')
        ->and($activities[1]->causer_id)->toBe($actor->getKey())
        ->and($activities[1]->getProperty('club_id'))->toBe($club->getKey());
})->with([
    'contribution type' => [ContributionType::class, 'contribution.type', ['interval' => 'yearly']],
    'membership type' => [MembershipType::class, 'membership-type', []],
    'department' => [Department::class, 'department', []],
    'club function' => [ClubFunction::class, 'club-function', []],
]);

it('audits rate changes and ignores saves without business changes', function (): void {
    [$club] = auditCoverageContext();
    $type = ContributionType::query()->create(['club_id' => $club->getKey(), 'name' => 'Beitrag', 'interval' => 'yearly']);
    $rate = ContributionRate::query()->create([
        'club_id' => $club->getKey(), 'contribution_type_id' => $type->getKey(),
        'amount' => '120.00', 'valid_from' => '2026-01-01',
    ]);

    $rate->update(['amount' => '150.00', 'is_active' => false]);
    $rate->save();

    $activity = Activity::query()->where('event', AuditAction::ContributionRateUpdated->value)->sole();
    expect($activity->getProperty('old.amount'))->toBe('120.00')
        ->and($activity->getProperty('new.amount'))->toBe('150.00')
        ->and($activity->getProperty('new.is_active'))->toBe(false);
    expect(Activity::query()->where('subject_id', $rate->getKey())->count())->toBe(2);

    $rate->delete();

    expect(Activity::query()->where('event', AuditAction::ContributionRateDeleted->value)->sole()->getProperty('old.amount'))->toBe('150.00');
});

it('audits individual contribution overrides with their reason and period', function (): void {
    [$club, $actor] = auditCoverageContext();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $type = ContributionType::query()->create(['club_id' => $club->getKey(), 'name' => 'Beitrag', 'interval' => 'yearly']);

    $override = app(MemberContributionOverrideManager::class)->create(
        $member, (string) $type->getKey(),
        MemberContributionOverrideType::FixedAmount,
        '80.00', CarbonImmutable::parse('2026-01-01'), null, 'Vorstandsbeschluss', $actor,
    );

    $activity = Activity::query()->where('event', AuditAction::MemberContributionOverrideCreated->value)->sole();
    expect($activity->getProperty('new.amount'))->toBe('80.00')
        ->and($activity->getProperty('new.reason'))->toBe('Vorstandsbeschluss')
        ->and($activity->getProperty('new.valid_from'))->toBe('2026-01-01')
        ->and($activity->causer_id)->toBe($actor->getKey());

    $override->update(['is_active' => false]);
    $override->delete();

    expect(Activity::query()->where('subject_id', $override->getKey())->pluck('event')->all())->toBe([
        AuditAction::MemberContributionOverrideCreated->value,
        AuditAction::MemberContributionOverrideUpdated->value,
        AuditAction::MemberContributionOverrideDeleted->value,
    ]);
});

it('audits document uploads without exposing storage paths', function (): void {
    Storage::fake('local');
    [$club, $actor] = auditCoverageContext();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $path = 'members/'.$club->getKey().'/'.$member->getKey().'/documents/document.pdf';
    Storage::disk('local')->put($path, 'Document body');

    $document = app(MemberDocumentManager::class)->registerStoredFile($member, $path, 'Dokument.pdf', MemberDocumentType::Other, $actor);

    $activity = Activity::query()->where('event', AuditAction::DocumentUploaded->value)->sole();
    expect((string) $activity->subject_id)->toBe((string) $document->getKey())
        ->and($activity->causer_id)->toBe($actor->getKey())
        ->and($activity->getProperty('new.original_name'))->toBe('Dokument.pdf')
        ->and($activity->properties->toArray())->not->toHaveKey('new.storage_path');
});

it('audits club access and role changes with the actor and previous role', function (): void {
    [$club, $actor] = auditCoverageContext();
    setPermissionsTeamId($club->getKey());
    SpatieRole::findOrCreate(Role::Member->value, 'web');
    SpatieRole::findOrCreate(Role::Board->value, 'web');
    $target = User::factory()->create();
    $manager = app(ClubUserManager::class);

    $manager->addUser($target, Role::Member);
    $manager->changeRole($target, Role::Board);
    $manager->changeRole($target, Role::Board);
    $manager->removeUser($target);

    $activities = Activity::query()->where('subject_id', $target->getKey())->orderBy('id')->get();
    expect($activities->pluck('event')->all())->toBe([
        AuditAction::ClubUserAdded->value, AuditAction::ClubUserRoleChanged->value, AuditAction::ClubUserRemoved->value,
    ]);
    expect($activities[1]->getProperty('old.roles'))->toBe([Role::Member->value])
        ->and($activities[1]->getProperty('new.roles'))->toBe([Role::Board->value])
        ->and($activities[1]->causer_id)->toBe($actor->getKey())
        ->and($activities[1]->getProperty('club_id'))->toBe($club->getKey());
    expect($target->clubs()->exists())->toBeFalse();
});

it('does not audit rejected club access changes', function (): void {
    [$club] = auditCoverageContext();
    $target = User::factory()->create();

    expect(fn () => app(ClubUserManager::class)->removeUser($target))->toThrow(InvalidArgumentException::class);
    expect(Activity::query()->where('subject_id', $target->getKey())->exists())->toBeFalse();
});

it('audits invitation creation resend and replacement without storing tokens', function (): void {
    Notification::fake();
    [$club, $actor] = auditCoverageContext();
    $manager = app(ClubInvitationManager::class);
    $first = $manager->create('invitee@example.com', Role::Member, $actor);
    $manager->resend($first['invitation']);
    $second = $manager->create('invitee@example.com', Role::Member, $actor);
    $manager->revoke($second['invitation']);
    $manager->revoke($second['invitation']);

    $activities = Activity::query()->where('subject_id', $first['invitation']->getKey())->orderBy('id')->get();
    expect($activities->pluck('event')->all())->toBe([
        AuditAction::ClubUserInvited->value, AuditAction::ClubInvitationResent->value, AuditAction::ClubInvitationRevoked->value,
    ]);
    expect($activities[0]->causer_id)->toBe($actor->getKey())
        ->and($activities[0]->getProperty('email'))->toBe('invitee@example.com');
    expect(Activity::query()->where('subject_id', $second['invitation']->getKey())->where('event', AuditAction::ClubInvitationRevoked->value)->count())->toBe(1);
    expect($activities->toJson())->not->toContain($first['token'])
        ->not->toContain('token_hash');
});

it('audits invitation acceptance with the accepting user', function (): void {
    Notification::fake();
    [$club, $actor] = auditCoverageContext();
    setPermissionsTeamId($club->getKey());
    SpatieRole::findOrCreate(Role::Member->value, 'web');
    $target = User::factory()->create(['email' => 'invitee@example.com']);
    $manager = app(ClubInvitationManager::class);
    $invitation = $manager->create($target->email, Role::Member, $actor);
    test()->actingAs($target);

    $manager->accept($invitation['token'], $target);

    $activity = Activity::query()->where('event', AuditAction::ClubInvitationAccepted->value)->sole();
    expect($activity->causer_id)->toBe($target->getKey())
        ->and($activity->getProperty('club_id'))->toBe($club->getKey())
        ->and($activity->getProperty('role'))->toBe(Role::Member->value);
});

it('audits authentication events through registered listeners', function (string $eventClass, AuditAction $action): void {
    $user = User::factory()->create();

    event(new $eventClass('web', $user, false));

    $activity = Activity::query()->where('event', $action->value)->sole();
    expect($activity->causer_id)->toBe($user->getKey())
        ->and((string) $activity->subject_id)->toBe((string) $user->getKey())
        ->and($activity->getProperty('guard'))->toBe('web');
})->with([
    'login' => [Login::class, AuditAction::UserLoggedIn],
    'logout' => [Logout::class, AuditAction::UserLoggedOut],
    'other devices' => [OtherDeviceLogout::class, AuditAction::OtherSessionsLoggedOut],
]);

it('audits password and MFA changes without recording secrets', function (): void {
    [$club, $actor] = auditCoverageContext();
    $actor->clubs()->attach($club);
    Http::preventStrayRequests();
    Http::fake(['https://api.pwnedpasswords.com/range/*' => Http::response('', 200)]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setTenant($club);

    Livewire::test(Profile::class)
        ->callAction('changePassword', data: [
            'current_password' => 'password',
            'password' => 'New-password-123!',
            'password_confirmation' => 'New-password-123!',
        ])
        ->assertHasNoActionErrors();
    $actor->saveAppAuthenticationSecret('test-secret');
    $actor->saveAppAuthenticationRecoveryCodes(['recovery-code']);
    $actor->saveAppAuthenticationSecret(null);

    $activities = Activity::query()->where('subject_id', $actor->getKey())->orderBy('id')->get();
    expect($activities->pluck('event')->all())->toBe([
        AuditAction::OtherSessionsLoggedOut->value, AuditAction::UserPasswordChanged->value, AuditAction::UserMfaEnabled->value, AuditAction::UserMfaDisabled->value,
    ]);
    expect($activities->toJson())->not->toContain('New-password-123!')
        ->not->toContain('test-secret')->not->toContain('recovery-code')->not->toContain($actor->password);
});

it('audits logging out other devices without claiming that the password changed', function (): void {
    [, $actor] = auditCoverageContext();

    Auth::logoutOtherDevices('password');

    expect(Activity::query()->where('subject_id', $actor->getKey())->pluck('event')->all())
        ->toBe([AuditAction::OtherSessionsLoggedOut->value]);
});

it('audits an address change once and retains original event context during later processing', function (): void {
    [$club, $actor] = auditCoverageContext();
    $memberId = (string) Str::uuid();
    MemberAggregate::retrieve($memberId)->register(
        (string) $club->getKey(), new MemberNumber('10001'), 'Max', 'Muster', null, CarbonImmutable::parse('2026-01-01'),
    )->persist();

    app(ChangeMemberAddressHandler::class)->handle(new ChangeMemberAddress($memberId, 'Straße', '1', '12345', 'Ort', 'DE'));

    $activity = Activity::query()->where('event', AuditAction::MemberAddressChanged->value)->sole();
    expect($activity->causer_id)->toBe($actor->getKey())
        ->and($activity->getProperty('club_id'))->toBe($club->getKey());

    $storedEvent = StoredEvent::query()->where('aggregate_uuid', $memberId)->orderByDesc('id')->firstOrFail();
    $otherActor = User::factory()->create();
    test()->actingAs($otherActor);
    app(CurrentClub::class)->set(Club::factory()->create());

    app(MemberAuditReactor::class)->onMemberAddressChanged($storedEvent->toStoredEvent()->event);

    $laterActivity = Activity::query()->where('event', AuditAction::MemberAddressChanged->value)->orderByDesc('id')->firstOrFail();
    expect($laterActivity->causer_id)->toBe($actor->getKey())
        ->and($laterActivity->getProperty('club_id'))->toBe($club->getKey())
        ->and($laterActivity->getProperty('stored_event_id'))->toBe($storedEvent->getKey());
});

it('keeps model changes and their audits together when a transaction rolls back', function (): void {
    [$club] = auditCoverageContext();

    try {
        DB::transaction(function () use ($club): void {
            MembershipType::query()->create(['club_id' => $club->getKey(), 'name' => 'Rollback']);
            throw new RuntimeException('Rollback');
        });
    } catch (RuntimeException) {
    }

    $this->assertDatabaseCount('membership_types', 0);
    expect(Activity::query()->where('event', AuditAction::MembershipTypeCreated->value)->exists())->toBeFalse();
});

it('audits member views only after authorization', function (bool $canView): void {
    [$club, $actor] = auditCoverageContext();
    $actor->clubs()->attach($club);
    setPermissionsTeamId($club->getKey());
    if ($canView) {
        $actor->givePermissionTo(SpatiePermission::findOrCreate(Permission::MembersView->value, 'web'));
    }
    $member = Member::factory()->create(['club_id' => $club->getKey()]);

    $response = $this->get(route('filament.admin.resources.members.view', ['tenant' => $club, 'record' => $member->getKey()]));

    if ($canView) {
        $response->assertSuccessful();
        $activity = Activity::query()->where('event', AuditAction::MemberViewed->value)->sole();
        expect($activity->causer_id)->toBe($actor->getKey())
            ->and((string) $activity->subject_id)->toBe((string) $member->getKey());
    } else {
        $response->assertForbidden();
        expect(Activity::query()->where('event', AuditAction::MemberViewed->value)->exists())->toBeFalse();
    }
})->with(['authorized' => true, 'missing permission' => false]);

it('does not attribute events without an original actor to the current request user', function (): void {
    [$club] = auditCoverageContext();
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $event = (new MemberAddressChanged('Straße', '1', '12345', 'Ort', 'DE'))
        ->setMetaData(['club_id' => $club->getKey()])
        ->setAggregateRootUuid((string) $member->getKey());

    app(MemberAuditReactor::class)->onMemberAddressChanged($event);

    $activity = Activity::query()->where('event', AuditAction::MemberAddressChanged->value)->sole();
    expect($activity->causer_id)->toBeNull()
        ->and($activity->getProperty('club_id'))->toBe($club->getKey())
        ->and($activity->getProperty('ip_address'))->toBeNull();
});
