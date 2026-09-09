<?php

namespace App\Application\Club;

use App\Domain\Club\Models\Club;

final class CurrentClub
{
    private ?Club $club = null;

    public function set(Club $club): void
    {
        $this->club = $club;
    }

    public function get(): Club
    {
        if ($this->club === null) {
            throw new \LogicException(
                'Es wurde kein Verein gesetzt.'
            );
        }

        return $this->club;
    }

    public function id(): string
    {
        return $this->get()->getKey();
    }

    public function hasClub(): bool
    {
        return $this->club !== null;
    }
}
