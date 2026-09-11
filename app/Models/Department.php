<?php

namespace App\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Membership\Models\Member;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Department extends Model
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

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            Member::class,
            'member_departments',
        )
            ->withPivot('joined_at')
            ->withTimestamps();
    }
}
