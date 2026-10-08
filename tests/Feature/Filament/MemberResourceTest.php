<?php

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Contribution\Models\PaymentAllocation;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;

uses(RefreshDatabase::class);

test('the current club cannot view a foreign member or their payment', function (): void {
    $club = Club::factory()->create();
    $foreignClub = Club::factory()->create();
    setPermissionsTeamId($club->getKey());
    $user = User::factory()->create();
    $user->clubs()->attach([$club->getKey(), $foreignClub->getKey()]);
    $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::MembersView->value, 'web'));
    $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::PaymentsView->value, 'web'));
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $foreignMember = Member::factory()->create(['club_id' => $foreignClub->getKey()]);
    Payment::query()->create([
        'club_id' => $foreignClub->getKey(),
        'member_id' => $foreignMember->getKey(),
        'status' => 'booked',
        'method' => 'bank_transfer',
        'amount' => '25.00',
        'booking_date' => '2026-10-08',
        'reference' => 'Vertrauliche Zahlung eines anderen Vereins',
    ]);

    $this->actingAs($user)->get(MemberResource::getUrl('view', ['tenant' => $club, 'record' => $member]))
        ->assertSuccessful()
        ->assertDontSee('Vertrauliche Zahlung eines anderen Vereins');
    $this->get(MemberResource::getUrl('view', ['tenant' => $club, 'record' => $foreignMember]))
        ->assertNotFound();
});

test('member view displays charge balances and the allocation based status', function (string $allocationAmount, string $paymentStatus, string $paidAmount, string $outstandingAmount, string $statusLabel): void {
    $club = Club::factory()->create();
    setPermissionsTeamId($club->getKey());
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::MembersView->value, 'web'));
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $type = ContributionType::query()->create([
        'club_id' => $club->getKey(),
        'name' => 'Jahresbeitrag',
        'interval' => 'yearly',
    ]);
    $charge = ContributionCharge::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'contribution_type_id' => $type->getKey(),
        'status' => 'open',
        'amount' => '120.00',
        'description' => 'Jahresbeitrag 2026',
        'period_from' => '2026-01-01',
        'due_date' => '2026-10-08',
    ]);
    $payment = Payment::query()->create([
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'status' => $paymentStatus,
        'method' => 'bank_transfer',
        'amount' => '120.00',
        'booking_date' => '2026-10-08',
    ]);
    if ($allocationAmount !== '0.00') {
        PaymentAllocation::query()->create([
            'club_id' => $club->getKey(),
            'payment_id' => $payment->getKey(),
            'contribution_charge_id' => $charge->getKey(),
            'amount' => $allocationAmount,
        ]);
    }

    $response = $this->actingAs($user)->get(MemberResource::getUrl('view', [
        'tenant' => $club,
        'record' => $member,
    ]));

    $response->assertSuccessful()->assertSeeInOrder([
        'Forderungsbetrag',
        Number::currency(120, in: 'EUR', locale: 'de'),
        'Bezahlt',
        Number::currency((float) $paidAmount, in: 'EUR', locale: 'de'),
        'Offen',
        Number::currency((float) $outstandingAmount, in: 'EUR', locale: 'de'),
        'Status',
        $statusLabel,
    ]);
})->with([
    'unallocated' => ['0.00', 'booked', '0.00', '120.00', 'Offen'],
    'partially paid' => ['50.00', 'booked', '50.00', '70.00', 'Teilweise bezahlt'],
    'fully paid' => ['120.00', 'booked', '120.00', '0.00', 'Bezahlt'],
    'reversed payment' => ['50.00', 'reversed', '0.00', '120.00', 'Offen'],
]);

test('member view displays payments with formatted values', function (string $status, string $method, ?string $reference, string $statusLabel, string $methodLabel): void {
    $club = Club::factory()->create();
    setPermissionsTeamId($club->getKey());
    $user = User::factory()->create();
    $user->clubs()->attach($club);
    $user->givePermissionTo(SpatiePermission::findOrCreate(Permission::MembersView->value, 'web'));
    $member = Member::factory()->create(['club_id' => $club->getKey()]);
    $otherMember = Member::factory()->create(['club_id' => $club->getKey()]);
    $payment = [
        'club_id' => $club->getKey(),
        'member_id' => $member->getKey(),
        'status' => $status,
        'method' => $method,
        'amount' => '25.00',
        'booking_date' => '2026-10-08',
        'reference' => $reference,
    ];
    Payment::query()->create($payment);
    Payment::query()->create(array_replace($payment, [
        'member_id' => $otherMember->getKey(),
        'reference' => 'Zahlung eines anderen Mitglieds',
    ]));

    $response = $this->actingAs($user)->get(MemberResource::getUrl('view', [
        'tenant' => $club,
        'record' => $member,
    ]));

    $response->assertSuccessful()
        ->assertSee('Zahlungen')
        ->assertSee('08.10.2026')
        ->assertSee(Number::currency(25, in: 'EUR', locale: 'de'))
        ->assertSee($methodLabel)
        ->assertSee($statusLabel)
        ->assertSee($reference ?? '—')
        ->assertDontSee('Zahlung eines anderen Mitglieds');
})->with([
    'booked bank transfer' => ['booked', 'bank_transfer', 'Beitrag Oktober 2026', 'Gebucht', 'Überweisung'],
    'reversed cash payment without reference' => ['reversed', 'cash', null, 'Storniert / zurückgebucht', 'Barzahlung'],
]);

test('filament admin members list page renders and register member action is accessible', function () {
    $club = Club::query()->create([
        'name' => 'Schützenverein Test e.V.',
        'short_name' => 'SV Test',
    ]);

    setPermissionsTeamId($club->getKey());

    $viewPermission = SpatiePermission::findOrCreate(
        Permission::MembersView->value,
        'web'
    );
    $createPermission = SpatiePermission::findOrCreate(
        Permission::MembersCreate->value,
        'web'
    );

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->clubs()->attach($club);
    $user->givePermissionTo([$viewPermission, $createPermission]);

    $response = $this->actingAs($user)->get(
        MemberResource::getUrl('index', [
            'tenant' => $club,
        ])
    );

    $response->assertSuccessful();
    $response->assertSee('Mitglieder');

    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    Livewire::actingAs($user)
        ->test(ListMembers::class)
        ->assertActionVisible('registerMember')
        ->mountAction('registerMember')
        ->assertHasNoActionErrors();
});

test('user can register a new member via register member action wizard', function () {
    $club = Club::query()->create([
        'name' => 'Schützenverein Test e.V.',
        'short_name' => 'SV Test',
    ]);

    setPermissionsTeamId($club->getKey());

    $viewPermission = SpatiePermission::findOrCreate(
        Permission::MembersView->value,
        'web'
    );
    $createPermission = SpatiePermission::findOrCreate(
        Permission::MembersCreate->value,
        'web'
    );

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->clubs()->attach($club);
    $user->givePermissionTo([$viewPermission, $createPermission]);

    $this->actingAs($user);
    Filament::setTenant($club);
    app(CurrentClub::class)->set($club);

    Livewire::actingAs($user)
        ->test(ListMembers::class)
        ->callAction('registerMember', data: [
            'member_number' => 'M-12345',
            'joined_at' => '2026-01-01',
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'birth_date' => '1990-05-15',
            'street' => 'Hauptstraße',
            'house_number' => '10',
            'postal_code' => '12345',
            'city' => 'Musterstadt',
            'country_code' => 'DE',
            'email' => 'max@example.com',
            'phone' => '+49123456789',
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('members', [
        'club_id' => $club->getKey(),
        'member_number' => 'M-12345',
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'email' => 'max@example.com',
    ]);
});
