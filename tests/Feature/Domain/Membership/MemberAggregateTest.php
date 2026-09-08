<?php

use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Events\MemberRegistered;
use App\Domain\Membership\Exceptions\MemberAlreadyRegistered;
use App\Domain\Membership\ValueObjects\MemberNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

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
            fn (MemberRegistered $event): bool =>
                $event->memberNumber === '10001'
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
