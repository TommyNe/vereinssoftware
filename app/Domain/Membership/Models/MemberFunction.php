<?php

namespace App\Domain\Membership\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MemberFunction extends Model
{
    use HasUuids;

    protected $table = 'member_functions';

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
            Member::class
        );
    }

    public function clubFunction(): BelongsTo
    {
        return $this->belongsTo(
            ClubFunction::class
        );
    }
}
