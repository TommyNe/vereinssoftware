<?php

namespace App\Listeners;

use App\Application\Audit\AuditLogger;
use App\Domain\Audit\Enums\AuditAction;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;

final readonly class LogLogout
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Logout|OtherDeviceLogout $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->log(
                $event instanceof OtherDeviceLogout ? AuditAction::OtherSessionsLoggedOut : AuditAction::UserLoggedOut,
                $event->user,
                $event->user,
                ['guard' => $event->guard],
            );
        }
    }
}
