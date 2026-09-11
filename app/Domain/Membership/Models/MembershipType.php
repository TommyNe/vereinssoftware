<?php

namespace App\Domain\Membership\Models;

use App\Domain\Club\Models\Club;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MembershipType extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
