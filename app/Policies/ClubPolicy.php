<?php

namespace App\Policies;

use App\Domain\Club\Models\Club;
use App\Models\User;

final class ClubPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->clubs()->exists();
    }

    public function view(
        User $user,
        Club $club,
    ): bool {
        return $user
            ->clubs()
            ->whereKey($club->getKey())
            ->exists();
    }

    public function create(User $user): bool
    {
        // Wichtig für das Onboarding:
        // Ein User ohne Club darf seinen ersten Verein anlegen.
        return true;
    }

    public function update(
        User $user,
        Club $club,
    ): bool {
        return $user
            ->clubs()
            ->whereKey($club->getKey())
            ->exists();
    }

    public function delete(
        User $user,
        Club $club,
    ): bool {
        return false;
    }

    public function restore(
        User $user,
        Club $club,
    ): bool {
        return false;
    }

    public function forceDelete(
        User $user,
        Club $club,
    ): bool {
        return false;
    }
}
