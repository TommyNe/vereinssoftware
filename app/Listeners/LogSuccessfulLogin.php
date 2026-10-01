<?php

namespace App\Listeners;

use App\Application\Audit\AuditLogger;
use App\Domain\Audit\Enums\AuditAction;
use App\Models\User;
use Illuminate\Auth\Events\Login;

final class LogSuccessfulLogin
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(
        Login $event,
    ): void {
        if ($event->user instanceof User) {
            $this->audit->log(AuditAction::UserLoggedIn, $event->user, $event->user, [
                'club_id' => null,
                'guard' => $event->guard,
            ]);
        }
    }
}
