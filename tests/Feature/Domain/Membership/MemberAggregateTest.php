<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Membership\Aggregates\MemberAggregate;
use App\Domain\Membership\Events\MemberAddressChanged;
use App\Domain\Membership\Events\MemberContactDataChanged;
use App\Domain\Membership\Events\MemberLeftClub;
use App\Domain\Membership\Events\MemberPersonalDataChanged;
use App\Domain\Membership\Events\MemberReactivated;
use App\Domain\Membership\Events\MemberRegistered;
use App\Domain\Membership\Events\MemberSuspended;
use App\Domain\Membership\Exceptions\MemberAlreadyLeftClub;
use App\Domain\Membership\Exceptions\MemberAlreadyRegistered;
use App\Domain\Membership\Exceptions\MemberNotRegistered;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\ValueObjects\Address;
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
            function (MemberRegistered $event): void {
                expect($event->memberNumber)->toBe('10001')
                    ->and($event->firstName)->toBe('Max')
                    ->and($event->lastName)->toBe('Mustermann');
            }
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

        $member = new Member([
            'uuid' => (string) Str::uuid(),
            'club_id' => $clubB->id,
            'member_number' => '20001',
            'first_name' => 'Erika',
            'last_name' => 'Mustermann',
            'joined_at' => CarbonImmutable::parse('2026-09-08'),
        ]);
        $member->writeable()->save();

        expect(
            Member::query()
                ->forCurrentClub()
                ->find($member->uuid)
        )->toBeNull();
    }
);

it('changes the address of a registered member', function (): void {
    $uuid = (string) Str::uuid();

    $aggregate = MemberAggregate::fake($uuid);

    $aggregate->given([
        new MemberRegistered(
            clubId: (string) Str::uuid(),
            memberNumber: '10001',
            firstName: 'Max',
            lastName: 'Mustermann',
            birthDate: null,
            joinedAt: '2026-01-01',
        ),
    ]);

    $aggregate->changeAddress(
        new Address(
            street: 'Dorfstraße',
            houseNumber: '15a',
            postalCode: '49733',
            city: 'Haren',
            countryCode: 'DE',
        )
    );

    $aggregate->assertRecorded(
        function (MemberAddressChanged $event): void {
            expect($event->street)->toBe('Dorfstraße')
                ->and($event->houseNumber)->toBe('15a')
                ->and($event->postalCode)->toBe('49733')
                ->and($event->city)->toBe('Haren')
                ->and($event->countryCode)->toBe('DE');
        }
    );
});

it(
    'cannot change the address of an unregistered member',
    function (): void {
        $aggregate = MemberAggregate::fake(
            (string) Str::uuid()
        );

        expect(
            fn () => $aggregate->changeAddress(
                new Address(
                    street: 'Dorfstraße',
                    houseNumber: '15',
                    postalCode: '49733',
                    city: 'Haren',
                    countryCode: 'DE',
                )
            )
        )->toThrow(
            MemberNotRegistered::class
        );
    }
);

it(
    'does not record an event when the address did not change',
    function (): void {
        $aggregate = MemberAggregate::fake(
            (string) Str::uuid()
        );

        $address = new Address(
            street: 'Dorfstraße',
            houseNumber: '15',
            postalCode: '49733',
            city: 'Haren',
            countryCode: 'DE',
        );

        $aggregate->given([
            new MemberRegistered(
                clubId: (string) Str::uuid(),
                memberNumber: '10001',
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: null,
                joinedAt: '2026-01-01',
            ),
            new MemberAddressChanged(
                street: 'Dorfstraße',
                houseNumber: '15',
                postalCode: '49733',
                city: 'Haren',
                countryCode: 'DE',
            ),
        ]);

        $aggregate->changeAddress($address);

        $aggregate->assertNothingRecorded();
    }
);

it(
    'updates the member read model after the address changed',
    function (): void {
        $club = Club::factory()->create();

        $uuid = (string) Str::uuid();

        MemberAggregate::retrieve($uuid)
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

        MemberAggregate::retrieve($uuid)
            ->changeAddress(
                new Address(
                    street: 'Dorfstraße',
                    houseNumber: '15',
                    postalCode: '49733',
                    city: 'Haren',
                    countryCode: 'DE',
                )
            )
            ->persist();

        $member = Member::query()
            ->findOrFail($uuid);

        expect($member->street)
            ->toBe('Dorfstraße')
            ->and($member->house_number)
            ->toBe('15')
            ->and($member->postal_code)
            ->toBe('49733')
            ->and($member->city)
            ->toBe('Haren')
            ->and($member->country_code)
            ->toBe('DE');
    }
);

it('changes contact data of a registered member', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->given([
            new MemberRegistered(
                clubId: (string) Str::uuid(),
                memberNumber: '10001',
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: null,
                joinedAt: '2026-01-01',
            ),
        ])
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->changeContactData(
                email: 'max@example.de',
                phone: '05932 123456',
                mobile: '0171 1234567',
            );
        })
        ->assertRecorded(
            new MemberContactDataChanged(
                email: 'max@example.de',
                phone: '05932 123456',
                mobile: '0171 1234567',
            )
        );
});

it('does not record an event when contact data did not change', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->given([
            new MemberRegistered(
                clubId: (string) Str::uuid(),
                memberNumber: '10001',
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: null,
                joinedAt: '2026-01-01',
            ),

            new MemberContactDataChanged(
                email: 'max@example.de',
                phone: '05932 123456',
                mobile: '0171 1234567',
            ),
        ])
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->changeContactData(
                email: 'max@example.de',
                phone: '05932 123456',
                mobile: '0171 1234567',
            );
        })
        ->assertNothingRecorded();
});

it('changes personal data of a registered member', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->given([
            new MemberRegistered(
                clubId: (string) Str::uuid(),
                memberNumber: '10001',
                firstName: 'Max',
                lastName: 'Meyer',
                birthDate: '1985-05-15',
                joinedAt: '2026-01-01',
            ),
        ])
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->changePersonalData(
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: '1985-05-15',
            );
        })
        ->assertRecorded(
            new MemberPersonalDataChanged(
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: '1985-05-15',
            )
        );
});

it('does not record an event when personal data did not change', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->given([
            new MemberRegistered(
                clubId: (string) Str::uuid(),
                memberNumber: '10001',
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: '1985-05-15',
                joinedAt: '2026-01-01',
            ),
        ])
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->changePersonalData(
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: '1985-05-15',
            );
        })
        ->assertNothingRecorded();
});

it('cannot change personal data of an unregistered member', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->changePersonalData(
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: '1985-05-15',
            );
        });
})->throws(MemberNotRegistered::class);

it('cannot change contact data of an unregistered member', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->changeContactData(
                email: 'max@example.de',
                phone: null,
                mobile: null,
            );
        });
})->throws(MemberNotRegistered::class);

it('projects all member changes', function (): void {
    $club = Club::factory()->create();

    $memberId = (string) Str::uuid();

    MemberAggregate::retrieve($memberId)
        ->register(
            clubId: $club->id,
            memberNumber: new MemberNumber('10001'),
            firstName: 'Max',
            lastName: 'Meyer',
            birthDate: CarbonImmutable::parse('1985-05-15'),
            joinedAt: CarbonImmutable::parse('2026-01-01'),
        )
        ->persist();

    MemberAggregate::retrieve($memberId)
        ->changeAddress(
            new Address(
                street: 'Dorfstraße',
                houseNumber: '15',
                postalCode: '49733',
                city: 'Haren',
                countryCode: 'DE',
            )
        )
        ->persist();

    MemberAggregate::retrieve($memberId)
        ->changeContactData(
            email: 'max@example.de',
            phone: '05932 123456',
            mobile: '0171 1234567',
        )
        ->persist();

    MemberAggregate::retrieve($memberId)
        ->changePersonalData(
            firstName: 'Max',
            lastName: 'Mustermann',
            birthDate: '1985-05-15',
        )
        ->persist();

    $member = Member::query()
        ->findOrFail($memberId);

    expect($member->first_name)
        ->toBe('Max')
        ->and($member->last_name)
        ->toBe('Mustermann')
        ->and($member->email)
        ->toBe('max@example.de')
        ->and($member->phone)
        ->toBe('05932 123456')
        ->and($member->mobile)
        ->toBe('0171 1234567')
        ->and($member->street)
        ->toBe('Dorfstraße')
        ->and($member->house_number)
        ->toBe('15')
        ->and($member->postal_code)
        ->toBe('49733')
        ->and($member->city)
        ->toBe('Haren')
        ->and($member->country_code)
        ->toBe('DE');
});

it('suspends an active member', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->given([
            new MemberRegistered(
                clubId: (string) Str::uuid(),
                memberNumber: '10001',
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: '1985-05-15',
                joinedAt: '2026-01-01',
            ),
        ])
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->suspend(
                CarbonImmutable::parse('2026-09-11'),
                'Vereinssperre',
            );
        })
        ->assertRecorded(
            new MemberSuspended(
                suspendedAt: '2026-09-11',
                reason: 'Vereinssperre',
            )
        );
});

it('does not suspend an already suspended member again', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->given([
            new MemberRegistered(
                clubId: (string) Str::uuid(),
                memberNumber: '10001',
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: null,
                joinedAt: '2026-01-01',
            ),

            new MemberSuspended(
                suspendedAt: '2026-09-01',
                reason: 'Test',
            ),
        ])
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->suspend(
                CarbonImmutable::parse('2026-09-11'),
                'Test',
            );
        })
        ->assertNothingRecorded();
});

it('cannot suspend a member who already left the club', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->given([
            new MemberRegistered(
                clubId: (string) Str::uuid(),
                memberNumber: '10001',
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: null,
                joinedAt: '2026-01-01',
            ),

            new MemberLeftClub(
                leftAt: '2026-08-31',
                reason: null,
            ),
        ])
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->suspend(
                CarbonImmutable::parse('2026-09-11'),
            );
        });
})->throws(MemberAlreadyLeftClub::class);

it('lets an active member leave the club', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->given([
            new MemberRegistered(
                clubId: (string) Str::uuid(),
                memberNumber: '10001',
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: null,
                joinedAt: '2026-01-01',
            ),
        ])
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->leave(
                CarbonImmutable::parse('2026-12-31'),
                'Eigener Wunsch',
            );
        })
        ->assertRecorded(
            new MemberLeftClub(
                leftAt: '2026-12-31',
                reason: 'Eigener Wunsch',
            )
        );
});

it('reactivates a suspended member', function (): void {
    MemberAggregate::fake((string) Str::uuid())
        ->given([
            new MemberRegistered(
                clubId: (string) Str::uuid(),
                memberNumber: '10001',
                firstName: 'Max',
                lastName: 'Mustermann',
                birthDate: null,
                joinedAt: '2026-01-01',
            ),

            new MemberSuspended(
                suspendedAt: '2026-09-01',
                reason: 'Test',
            ),
        ])
        ->when(function (MemberAggregate $aggregate): void {
            $aggregate->reactivate(
                CarbonImmutable::parse('2026-09-11'),
            );
        })
        ->assertRecorded(
            new MemberReactivated(
                reactivatedAt: '2026-09-11',
            )
        );
});
