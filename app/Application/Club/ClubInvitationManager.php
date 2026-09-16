<?php

namespace App\Application\Club;

use App\Domain\Club\Models\ClubInvitation;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use App\Notifications\ClubInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class ClubInvitationManager
{
    public function __construct(
        private CurrentClub $currentClub,
        private ClubUserManager $clubUserManager,
    ) {}

    /**
     * @throws \Throwable
     */
    public function create(
        string $email,
        Role $role,
        User $invitedBy,
    ): array {
        $email = mb_strtolower(
            trim($email)
        );

        $club = $this->currentClub->get();

        if (
            User::query()
                ->where('email', $email)
                ->whereHas(
                    'clubs',
                    fn ($query) => $query->whereKey(
                        $club->getKey()
                    )
                )
                ->exists()
        ) {
            throw new InvalidArgumentException(
                'Dieser Benutzer gehört bereits zum Verein.'
            );
        }

        $token = Str::random(64);

        $invitation = DB::transaction(
            function () use (
                $club,
                $email,
                $role,
                $token,
                $invitedBy,
            ): ClubInvitation {
                ClubInvitation::query()
                    ->where(
                        'club_id',
                        $club->getKey()
                    )
                    ->where(
                        'email',
                        $email
                    )
                    ->whereNull(
                        'accepted_at'
                    )
                    ->whereNull(
                        'revoked_at'
                    )
                    ->update([
                        'revoked_at' => now(),
                    ]);

                return ClubInvitation::query()
                    ->create([
                        'club_id' => $club->getKey(),

                        'email' => $email,

                        'role' => $role->value,

                        'token_hash' => hash(
                            'sha256',
                            $token
                        ),

                        'invited_by' => $invitedBy->getKey(),

                        'expires_at' => now()->addDays(7),
                    ]);
            }
        );

        Notification::route(
            'mail',
            $email,
        )->notify(
            new ClubInvitationNotification(
                invitation: $invitation,
                token: $token,
            )
        );

        return [
            'invitation' => $invitation,
            'token' => $token,
        ];
    }

    /**
     * @throws \Throwable
     */
    public function accept(
        string $token,
        User $user,
    ): ClubInvitation {
        $invitation = ClubInvitation::query()
            ->with('club')
            ->where(
                'token_hash',
                hash(
                    'sha256',
                    $token
                )
            )
            ->first();

        if (
            ! $invitation instanceof ClubInvitation
        ) {
            throw new InvalidArgumentException(
                'Ungültige Einladung.'
            );
        }

        if (! $invitation->isUsable()) {
            throw new InvalidArgumentException(
                'Die Einladung ist nicht mehr gültig.'
            );
        }

        if (
            mb_strtolower(
                trim($user->email)
            )
            !==
            mb_strtolower(
                trim($invitation->email)
            )
        ) {
            throw new InvalidArgumentException(
                'Die Einladung gehört zu einer anderen E-Mail-Adresse.'
            );
        }

        $role = Role::tryFrom(
            $invitation->role
        );

        if ($role === null) {
            throw new InvalidArgumentException(
                'Die hinterlegte Rolle ist ungültig.'
            );
        }

        DB::transaction(
            function () use (
                $invitation,
                $user,
                $role,
            ): void {
                /*
                 * CurrentClub temporär auf den
                 * eingeladenen Club setzen.
                 */
                $this->currentClub->set(
                    $invitation->club
                );

                $this->clubUserManager
                    ->addUser(
                        user: $user,
                        role: $role,
                    );

                $invitation->update([
                    'accepted_at' => now(),
                ]);
            }
        );

        return $invitation;
    }
}
