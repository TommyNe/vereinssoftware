<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

final class LogSuccessfulLogin
{
    public function handle(
        Login $event,
    ): void {
        // später AuditLogger
    }
}
