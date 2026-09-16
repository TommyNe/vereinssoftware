<?php

namespace App\Application\Club;

use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ClubUserManager
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    /**
     * @throws \Throwable
     */
    public function addUser(
        User $user,
        Role $role,
    ): void {
        DB::transaction(
            function () use (
                $user,
                $role,
            ): void {
                $club = $this->currentClub
                    ->get();

                $this->ensureUserIsNotMember(
                    $user,
                    $club,
                );

                $user
                    ->clubs()
                    ->attach(
                        $club->getKey()
                    );

                $this->setTeam(
                    user: $user,
                    club: $club,
                );

                $user->assignRole(
                    $role->value
                );
            }
        );
    }

    /**
     * @throws \Throwable
     */
    public function changeRole(
        User $user,
        Role $role,
    ): void {
        $club = $this->currentClub
            ->get();

        $this->ensureUserBelongsToClub(
            user: $user,
            club: $club,
        );

        if (
            $this->isLastAdministrator(
                user: $user,
                club: $club,
            )
        ) {
            throw new InvalidArgumentException(
                'Der letzte Administrator kann nicht aus dem Verein entfernt werden.'
            );
        }

        $this->setTeam(
            user: $user,
            club: $club,
        );

        $user->syncRoles([
            $role->value,
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function removeUser(
        User $user,
    ): void {
        DB::transaction(
            function () use ($user): void {
                $club = $this->currentClub
                    ->get();

                $this->ensureUserBelongsToClub(
                    user: $user,
                    club: $club,
                );

                if (
                    $this->isLastAdministrator(
                        user: $user,
                        club: $club,
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Der letzte Administrator kann nicht aus dem Verein entfernt werden.'
                    );
                }

                $this->setTeam(
                    user: $user,
                    club: $club,
                );

                foreach (
                    $user->getRoleNames() as $role
                ) {
                    $user->removeRole(
                        $role
                    );
                }

                $user
                    ->clubs()
                    ->detach(
                        $club->getKey()
                    );
            }
        );
    }

    /**
     * @throws \Throwable
     */
    private function setTeam(
        User $user,
        Club $club,
    ): void {
        setPermissionsTeamId(
            $club->getKey()
        );

        $user->unsetRelation('roles');
        $user->unsetRelation(
            'permissions'
        );
    }

    /**
     * @throws \Throwable
     */
    private function ensureUserBelongsToClub(
        User $user,
        Club $club,
    ): void {
        if (
            ! $user
                ->clubs()
                ->whereKey(
                    $club->getKey()
                )
                ->exists()
        ) {
            throw new InvalidArgumentException(
                'Der Benutzer gehört nicht zum aktuellen Verein.'
            );
        }
    }

    /**
     * @throws \Throwable
     */
    private function ensureUserIsNotMember(
        User $user,
        Club $club,
    ): void {
        if (
            $user
                ->clubs()
                ->whereKey(
                    $club->getKey()
                )
                ->exists()
        ) {
            throw new InvalidArgumentException(
                'Der Benutzer gehört bereits zum Verein.'
            );
        }
    }

    /**
     * @throws \Throwable
     */
    private function isLastAdministrator(
        User $user,
        Club $club,
    ): bool {
        $this->setTeam(
            user: $user,
            club: $club,
        );

        if (
            ! $user->hasRole(
                Role::Administrator->value
            )
        ) {
            return false;
        }

        $administratorCount = $club
            ->users()
            ->get()
            ->filter(
                function (
                    User $clubUser
                ) use ($club): bool {
                    $this->setTeam(
                        user: $clubUser,
                        club: $club,
                    );

                    return $clubUser->hasRole(
                        Role::Administrator
                            ->value
                    );
                }
            )
            ->count();

        return $administratorCount <= 1;
    }
}
