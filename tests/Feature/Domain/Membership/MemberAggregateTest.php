<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Events\MemberRegistered;
use App\Domain\Membership\Exceptions\MemberAlreadyRegistered;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\ValueObjects\MemberNumber;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('registers a member', function (): void {
    $uuid = (string) Str::uuid();

    $aggregate = MemberAggregate::fake($uuid);

    $aggregate
        ->register(
            clubId: (string) Str::uuid(),
            memberNumber: new MemberNumber('10001'),
            firstName: 'Max',
            lastName: 'Mustermann',
            birthDate: CarbonImmutable::parse('1984-05-17'),
            joinedAt: CarbonImmutable::parse('2026-09-08'),
        )
        ->assertRecorded(
            fn (MemberRegistered $event): bool => $event->memberNumber === '10001'
                && $event->firstName === 'Max'
                && $event->lastName === 'Mustermann'
        );
});

it('cannot register the same aggregate twice', function (): void {
    $uuid = (string) Str::uuid();

    $aggregate = MemberAggregate::fake($uuid);

    $aggregate->register(
        clubId: (string) Str::uuid(),
        memberNumber: new MemberNumber('10001'),
        firstName: 'Max',
        lastName: 'Mustermann',
        birthDate: null,
        joinedAt: CarbonImmutable::parse('2026-09-08'),
    );

    expect(
        fn () => $aggregate->register(
            clubId: (string) Str::uuid(),
            memberNumber: new MemberNumber('10002'),
            firstName: 'Thomas',
            lastName: 'Test',
            birthDate: null,
            joinedAt: CarbonImmutable::parse('2026-09-08'),
        )
    )->toThrow(MemberAlreadyRegistered::class);
});

it(
    'does not allow access to members of another club',
    function (): void {
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();

        $user = User::factory()->create();

        $user->clubs()->attach($clubA);

        session([
            'current_club_id' => $clubA->id,
        ]);

        app(CurrentClub::class)->set($clubA);

        setPermissionsTeamId($clubA->id);

        $member = Member::query()->create([
            'id' => (string) Str::uuid(),
            'club_id' => $clubB->id,
            'member_number' => '20001',
            'first_name' => 'Erika',
            'last_name' => 'Mustermann',
            'joined_at' => CarbonImmutable::parse('2026-09-08'),
        ]);

        expect(
            Member::query()
                ->forCurrentClub()
                ->find($member->id)
        )->toBeNull();
    }
);
