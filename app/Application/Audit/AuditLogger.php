<?php

namespace App\Application\Audit;

use App\Application\Club\CurrentClub;
use App\Domain\Audit\Enums\AuditAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final readonly class AuditLogger
{
    public function __construct(
        private CurrentClub $currentClub,
    ) {}

    public function log(
        AuditAction $action,
        Model $subject,
        ?User $user = null,
        array $properties = [],
        bool $useRequestContext = true,
    ): void {
        $request = request();

        if ($useRequestContext) {
            $user ??= $request->user() ?? auth()->user();
        }

        $activity = activity('security')
            ->performedOn($subject)
            ->event($action->value)
            ->withProperties([
                'club_id' => $useRequestContext && $this->currentClub->hasClub()
                    ? $this->currentClub->id()
                    : null,

                'ip_address' => $useRequestContext ? $request->ip() : null,

                'user_agent' => $useRequestContext ? $request->userAgent() : null,

                'method' => $useRequestContext ? $request->method() : null,

                'path' => $useRequestContext ? $request->path() : null,

                ...$properties,
            ]);

        if ($user !== null) {
            $activity->causedBy($user);
        } else {
            $activity->causedByAnonymous();
        }

        $activity->log($action->value);
    }
}
