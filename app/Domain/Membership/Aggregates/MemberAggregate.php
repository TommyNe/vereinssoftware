<?php

namespace App\Domain\Membership\Aggregates;

use App\Domain\Membership\Events\MemberAddressChanged;
use App\Domain\Membership\Events\MemberContactDataChanged;
use App\Domain\Membership\Events\MemberRegistered;
use App\Domain\Membership\Exceptions\MemberAlreadyRegistered;
use App\Domain\Membership\Exceptions\MemberNotRegistered;
use App\Domain\Membership\ValueObjects\Address;
use App\Domain\Membership\ValueObjects\MemberNumber;
use Carbon\CarbonImmutable;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

final class MemberAggregate extends AggregateRoot
{
    private bool $registered = false;

    private ?string $clubId = null;

    private ?string $memberNumber = null;

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

    protected function applyMemberRegistered(
        MemberRegistered $event
    ): void {
        $this->registered = true;
        $this->clubId = $event->clubId;
        $this->memberNumber = $event->memberNumber;
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
}
