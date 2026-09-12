<?php

namespace App\Infrastructure\EventSourcing;

use App\Application\Club\CurrentClub;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Spatie\SchemalessAttributes\SchemalessAttributes;

/**
 * @property-read SchemalessAttributes $meta_data
 */
final class StoredEvent extends EloquentStoredEvent
{
    protected static function booted(): void
    {
        self::creating(
            function (self $storedEvent): void {
                $user = auth()->user();

                if ($user !== null) {
                    $storedEvent->meta_data->set('user_id', $user->getKey());
                }

                $currentClub = app(CurrentClub::class);

                if ($currentClub->hasClub()) {
                    $storedEvent->meta_data->set('club_id', $currentClub->id());
                }

                if (app()->runningInConsole()) {
                    $storedEvent->meta_data->set('source', 'console');
                } else {
                    $storedEvent->meta_data->set('source', 'http');
                    $storedEvent->meta_data->set('ip_address', request()->ip());
                }
            }
        );
    }
}
