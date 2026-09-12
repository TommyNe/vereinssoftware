<?php

use App\Application\Audit\AuditLogger;
use App\Application\Club\CurrentClub;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Club\Models\Club;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\ValueObjects\MemberNumber;
use App\Infrastructure\EventSourcing\StoredEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('logs a member security activity', function (): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();

    app(CurrentClub::class)
        ->set($club);

    $member = Member::factory()->create([
        'club_id' => $club->id,
    ]);

    $this->actingAs($user);

    app(AuditLogger::class)->log(
        AuditAction::MemberViewed,
        $member,
    );

    $activity = Activity::query()
        ->latest('id')
        ->firstOrFail();

    expect($activity->event)
        ->toBe(
            AuditAction::MemberViewed->value
        )
        ->and($activity->causer_id)
        ->toBe($user->id)
        ->and((string) $activity->subject_id)
        ->toBe((string) $member->uuid)
        ->and(
            $activity->getProperty(
                'club_id'
            )
        )
        ->toBe($club->id);
});

it('stores actor metadata with domain events', function (): void {
    $club = Club::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    app(CurrentClub::class)
        ->set($club);

    $memberId = (string) Str::uuid();

    MemberAggregate::retrieve(
        $memberId
    )
        ->register(
            clubId: $club->id,
            memberNumber: new MemberNumber('10001'),
            firstName: 'Max',
            lastName: 'Mustermann',
            birthDate: null,
            joinedAt: CarbonImmutable::parse(
                '2026-01-01'
            ),
        )
        ->persist();

    $storedEvent =
        StoredEvent::query()
            ->where(
                'aggregate_uuid',
                $memberId
            )
            ->firstOrFail();

    expect(
        $storedEvent->meta_data[
        'user_id'
        ]
    )->toBe($user->id)
        ->and(
            $storedEvent->meta_data[
            'club_id'
            ]
        )
        ->toBe($club->id);
});
