<?php

namespace App\Domain\Membership\Aggregates;

use App\Domain\Membership\Enums\MembershipStatus;
use App\Domain\Membership\Events\MemberAddressChanged;
use App\Domain\Membership\Events\MemberContactDataChanged;
use App\Domain\Membership\Events\MemberFunctionAssigned;
use App\Domain\Membership\Events\MemberFunctionEnded;
use App\Domain\Membership\Events\MemberJoinedDepartment;
use App\Domain\Membership\Events\MemberLeftClub;
use App\Domain\Membership\Events\MemberLeftDepartment;
use App\Domain\Membership\Events\MemberPersonalDataChanged;
use App\Domain\Membership\Events\MemberReactivated;
use App\Domain\Membership\Events\MemberRegistered;
use App\Domain\Membership\Events\MembershipTypeChanged;
use App\Domain\Membership\Events\MemberSuspended;
use App\Domain\Membership\Exceptions\InvalidFunctionPeriod;
use App\Domain\Membership\Exceptions\MemberAlreadyLeftClub;
use App\Domain\Membership\Exceptions\MemberAlreadyRegistered;
use App\Domain\Membership\Exceptions\MemberDoesNotHaveFunction;
use App\Domain\Membership\Exceptions\MemberNotInDepartment;
use App\Domain\Membership\Exceptions\MemberNotRegistered;
use App\Domain\Membership\ValueObjects\Address;
use App\Domain\Membership\ValueObjects\MemberNumber;
use Carbon\CarbonImmutable;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

final class MemberAggregate extends AggregateRoot
{
    private bool $registered = false;

    private ?string $membershipTypeId = null;

    /**
     * @var array<string, string>
     *
     * clubFunctionId => validFrom
     */
    private array $functionAssignments = [];

    private ?MembershipStatus $status = null;

    /**
     * @var array<string, true>
     */
    private array $departmentIds = [];

    private ?string $firstName = null;

    private ?string $lastName = null;

    private ?string $birthDate = null;

    private ?string $email = null;

    private ?string $phone = null;

    private ?string $mobile = null;

    private ?Address $address = null;

    public function register(
        string $clubId,
        MemberNumber $memberNumber,
        string $firstName,
        string $lastName,
        ?CarbonImmutable $birthDate,
        CarbonImmutable $joinedAt,
    ): self {
        if ($this->registered) {
            throw MemberAlreadyRegistered::create();
        }

        $this->recordThat(
            new MemberRegistered(
                clubId: $clubId,
                memberNumber: $memberNumber->value(),
                firstName: $firstName,
                lastName: $lastName,
                birthDate: $birthDate?->toDateString(),
                joinedAt: $joinedAt->toDateString(),
            )
        );

        return $this;
    }

    public function suspend(
        CarbonImmutable $suspendedAt,
        ?string $reason = null,
    ): self {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        if ($this->status === MembershipStatus::Left) {
            throw MemberAlreadyLeftClub::create();
        }

        if ($this->status === MembershipStatus::Suspended) {
            return $this;
        }

        $this->recordThat(
            new MemberSuspended(
                suspendedAt: $suspendedAt->toDateString(),
                reason: $reason,
            )
        );

        return $this;
    }

    public function reactivate(CarbonImmutable $reactivatedAt): self
    {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        if ($this->status === MembershipStatus::Left) {
            throw MemberAlreadyLeftClub::create();
        }

        if ($this->status === MembershipStatus::Active) {
            return $this;
        }

        $this->recordThat(
            new MemberReactivated(
                reactivatedAt: $reactivatedAt->toDateString(),
            )
        );

        return $this;
    }

    public function leave(
        CarbonImmutable $leftAt,
        ?string $reason = null,
    ): self {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        if ($this->status === MembershipStatus::Left) {
            throw MemberAlreadyLeftClub::create();
        }

        $this->recordThat(
            new MemberLeftClub(
                leftAt: $leftAt->toDateString(),
                reason: $reason,
            )
        );

        return $this;
    }

    public function changeAddress(Address $address): self
    {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        if (
            $this->address !== null
            && $this->address->equals($address)
        ) {
            return $this;
        }

        $this->recordThat(
            new MemberAddressChanged(
                street: $address->street,
                houseNumber: $address->houseNumber,
                postalCode: $address->postalCode,
                city: $address->city,
                countryCode: $address->countryCode,
            )
        );

        return $this;
    }

    public function changeContactData(
        ?string $email,
        ?string $phone,
        ?string $mobile,
    ): self {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        if (
            $this->email === $email
            && $this->phone === $phone
            && $this->mobile === $mobile
        ) {
            return $this;
        }

        $this->recordThat(
            new MemberContactDataChanged(
                email: $email,
                phone: $phone,
                mobile: $mobile,
            )
        );

        return $this;
    }

    public function changePersonalData(
        string $firstName,
        string $lastName,
        ?string $birthDate,
    ): self {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        if (
            $this->firstName === $firstName
            && $this->lastName === $lastName
            && $this->birthDate === $birthDate
        ) {
            return $this;
        }

        $this->recordThat(
            new MemberPersonalDataChanged(
                firstName: $firstName,
                lastName: $lastName,
                birthDate: $birthDate,
            )
        );

        return $this;
    }

    public function changeMembershipType(
        string $membershipTypeId,
    ): self {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        if ($this->status === MembershipStatus::Left) {
            throw MemberAlreadyLeftClub::create();
        }

        if (
            $this->membershipTypeId ===
            $membershipTypeId
        ) {
            return $this;
        }

        $this->recordThat(
            new MembershipTypeChanged(
                membershipTypeId: $membershipTypeId,
            )
        );

        return $this;
    }

    public function joinDepartment(
        string $departmentId,
        CarbonImmutable $joinedAt,
    ): self {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        if (
            $this->status ===
            MembershipStatus::Left
        ) {
            throw MemberAlreadyLeftClub::create();
        }

        if (
            isset(
                $this->departmentIds[
                $departmentId
                ]
            )
        ) {
            return $this;
        }

        $this->recordThat(
            new MemberJoinedDepartment(
                departmentId: $departmentId,
                joinedAt: $joinedAt->toDateString(),
            )
        );

        return $this;
    }

    public function leaveDepartment(
        string $departmentId,
        CarbonImmutable $leftAt,
    ): self {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        if (
            ! isset(
                $this->departmentIds[
                $departmentId
                ]
            )
        ) {
            throw MemberNotInDepartment::create();
        }

        $this->recordThat(
            new MemberLeftDepartment(
                departmentId: $departmentId,
                leftAt: $leftAt->toDateString(),
            )
        );

        return $this;
    }

    public function assignFunction(
        string $clubFunctionId,
        CarbonImmutable $validFrom,
    ): self {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        if (
            $this->status ===
            MembershipStatus::Left
        ) {
            throw MemberAlreadyLeftClub::create();
        }

        if (
            isset(
                $this->functionAssignments[
                $clubFunctionId
                ]
            )
        ) {
            return $this;
        }

        $this->recordThat(
            new MemberFunctionAssigned(
                clubFunctionId: $clubFunctionId,
                validFrom: $validFrom->toDateString(),
            )
        );

        return $this;
    }

    public function endFunction(
        string $clubFunctionId,
        CarbonImmutable $validUntil,
    ): self {
        if (! $this->registered) {
            throw MemberNotRegistered::create();
        }

        $validFrom =
            $this->functionAssignments[
            $clubFunctionId
            ] ?? null;

        if ($validFrom === null) {
            throw MemberDoesNotHaveFunction::create();
        }

        if (
            $validUntil->lt(
                CarbonImmutable::parse($validFrom)
            )
        ) {
            throw InvalidFunctionPeriod::create();
        }

        $this->recordThat(
            new MemberFunctionEnded(
                clubFunctionId: $clubFunctionId,
                validUntil: $validUntil->toDateString(),
            )
        );

        return $this;
    }

    protected function applyMemberRegistered(
        MemberRegistered $event
    ): void {
        $this->registered = true;

        $this->firstName = $event->firstName;
        $this->lastName = $event->lastName;
        $this->birthDate = $event->birthDate;

        $this->status = MembershipStatus::Active;
    }

    protected function applyMemberAddressChanged(
        MemberAddressChanged $event,
    ): void {
        $this->address = new Address(
            street: $event->street,
            houseNumber: $event->houseNumber,
            postalCode: $event->postalCode,
            city: $event->city,
            countryCode: $event->countryCode,
        );
    }

    protected function applyMemberContactDataChanged(
        MemberContactDataChanged $event,
    ): void {
        $this->email = $event->email;
        $this->phone = $event->phone;
        $this->mobile = $event->mobile;
    }

    protected function applyMemberPersonalDataChanged(
        MemberPersonalDataChanged $event,
    ): void {
        $this->firstName = $event->firstName;
        $this->lastName = $event->lastName;
        $this->birthDate = $event->birthDate;
    }

    protected function applyMemberSuspended(
        MemberSuspended $event,
    ): void {
        $this->status = MembershipStatus::Suspended;
    }

    protected function applyMemberReactivated(
        MemberReactivated $event,
    ): void {
        $this->status = MembershipStatus::Active;
    }

    protected function applyMemberLeftClub(
        MemberLeftClub $event,
    ): void {
        $this->status = MembershipStatus::Left;
    }

    protected function applyMembershipTypeChanged(
        MembershipTypeChanged $event,
    ): void {
        $this->membershipTypeId =
            $event->membershipTypeId;
    }

    protected function applyMemberJoinedDepartment(
        MemberJoinedDepartment $event,
    ): void {
        $this->departmentIds[
        $event->departmentId
        ] = true;
    }

    protected function applyMemberLeftDepartment(
        MemberLeftDepartment $event,
    ): void {
        unset(
            $this->departmentIds[
            $event->departmentId
            ]
        );
    }

    protected function applyMemberFunctionAssigned(
        MemberFunctionAssigned $event,
    ): void {
        $this->functionAssignments[
        $event->clubFunctionId
        ] = $event->validFrom;
    }

    protected function applyMemberFunctionEnded(
        MemberFunctionEnded $event,
    ): void {
        unset(
            $this->functionAssignments[
            $event->clubFunctionId
            ]
        );
    }
}
