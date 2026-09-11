<?php

namespace App\Domain\Membership\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EventSourcing\Projections\Projection;

final class MemberFunction extends Projection
{
    use HasUuids;

    protected $table = 'member_functions';

    protected $primaryKey = 'id';

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(
            Member::class,
            'member_id',
        );
    }

    public function clubFunction(): BelongsTo
    {
        return $this->belongsTo(
            ClubFunction::class
        );
    }
}
