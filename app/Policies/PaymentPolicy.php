<?php

namespace App\Policies;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;

readonly class PaymentPolicy
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function view(
        User $user,
        Payment $payment,
    ): bool {
        return
            $this->currentClub->hasClub()
            && (string) $payment->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::PaymentsView->value
            );
    }

    public function create(
        User $user,
    ): bool {
        return $this->currentClub->hasClub() && $user->can(
            Permission::PaymentsManage->value
        );
    }

    public function reverse(
        User $user,
        Payment $payment,
    ): bool {
        return
            $this->currentClub->hasClub()
            && (string) $payment->club_id
            === $this->currentClub->id()
            && $user->can(
                Permission::PaymentsReverse->value
            );
    }

    public function delete(
        User $user,
        Payment $payment,
    ): bool {
        return false;
    }
}
