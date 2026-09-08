<?php

namespace App\Domain\Membership\Aggregates;

use App\Domain\Membership\Events\MemberRegistered;
use App\Domain\Membership\Exceptions\MemberAlreadyRegistered;
use App\Domain\Membership\ValueObjects\MemberNumber;
use Carbon\CarbonImmutable;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

final class MemberAggregate extends AggregateRoot
{
    private bool $registered = false;

    private ?string $clubId = null;

    private ?string $memberNumber = null;

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

    protected function applyMemberRegistered(
        MemberRegistered $event
    ): void {
        $this->registered = true;
        $this->clubId = $event->clubId;
        $this->memberNumber = $event->memberNumber;
    }
}
