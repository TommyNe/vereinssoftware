<?php

namespace App\Application\Audit;

use App\Domain\Audit\Enums\AuditAction;
use App\Models\User;

final readonly class UserSecurityObserver
{
    public function __construct(private AuditLogger $audit) {}

    public function updated(User $user): void
    {
        if ($user->wasChanged('app_authentication_secret')) {
            $wasEnabled = $user->getRawOriginal('app_authentication_secret') !== null;
            $isEnabled = $user->getAttributes()['app_authentication_secret'] !== null;

            if ($wasEnabled !== $isEnabled) {
                $this->audit->log(
                    $isEnabled ? AuditAction::UserMfaEnabled : AuditAction::UserMfaDisabled,
                    $user,
                );
            }
        }
    }
}
