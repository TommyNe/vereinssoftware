<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Models\User;

final readonly class SepaDebitRunPolicy
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function viewAny(
        User $user,
    ): bool {
        return $user->can(
            Permission::SepaDebitRunsView->value
        );
    }

    public function view(
        User $user,
        SepaDebitRun $run,
    ): bool {
        return
            (string) $run->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::SepaDebitRunsView->value
            );
    }

    public function create(
        User $user,
    ): bool {
        return $user->can(
            Permission::SepaDebitRunsCreate->value
        );
    }

    public function update(
        User $user,
        SepaDebitRun $run,
    ): bool {
        return false;
    }

    public function delete(
        User $user,
        SepaDebitRun $run,
    ): bool {
        return false;
    }

    public function export(
        User $user,
        SepaDebitRun $run,
    ): bool {
        return
            (string) $run->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::SepaDebitRunsExport->value
            );
    }

    public function submit(
        User $user,
        SepaDebitRun $run,
    ): bool {
        return
            (string) $run->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::SepaDebitRunsSubmit
                    ->value
            );
    }
}
