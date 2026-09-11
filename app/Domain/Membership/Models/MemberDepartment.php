<?php

namespace App\Domain\Membership\Models;

use App\Models\Department;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EventSourcing\Projections\Projection;

final class MemberDepartment extends Projection
{
    use HasUuids;

    protected $table = 'member_departments';

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
            'joined_at' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(
            Member::class,
            'member_id',
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }
}
