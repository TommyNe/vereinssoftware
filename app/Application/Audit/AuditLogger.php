<?php

namespace App\Application\Audit;

use App\Application\Club\CurrentClub;
use App\Domain\Audit\Enums\AuditAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final readonly class AuditLogger
{
    public function __construct(
        private CurrentClub $currentClub,
        private Request $request,
    ) {
    }

    public function log(
        AuditAction $action,
        Model $subject,
        ?User $user = null,
        array $properties = [],
    ): void {
        $user ??= $this->request->user();

        $activity = activity('security')
            ->performedOn($subject)
            ->event($action->value)
            ->withProperties([
                'club_id' => $this->currentClub->hasClub()
                    ? $this->currentClub->id()
                    : null,

                'ip_address' =>
                    $this->request->ip(),

                'user_agent' =>
                    $this->request->userAgent(),

                'method' =>
                    $this->request->method(),

                'path' =>
                    $this->request->path(),

                ...$properties,
            ]);

        if ($user !== null) {
            $activity->causedBy($user);
        }

        $activity->log($action->value);
    }
}
