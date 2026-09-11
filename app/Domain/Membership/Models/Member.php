<?php

namespace App\Domain\Membership\Models;

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Membership\Enums\MembershipStatus;
use App\Models\Department;
use Database\Factories\Domain\Membership\Models\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EventSourcing\Projections\Projection;

final class Member extends Projection
{
    use HasFactory;

    protected $table = 'members';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'joined_at' => 'date',
            'left_at' => 'date',

            'status' => MembershipStatus::class,
        ];
    }

    protected static function newFactory(): MemberFactory
    {
        return MemberFactory::new();
    }

    public function scopeForCurrentClub(
        Builder $query
    ): Builder {
        $currentClub = app(CurrentClub::class);

        return $query->where(
            'club_id',
            $currentClub->id(),
        );
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(
            MembershipType::class
        );
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(
            Department::class,
            'member_departments',
            'member_id',
            'department_id',
        )
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function departmentMemberships(): HasMany
    {
        return $this->hasMany(
            MemberDepartment::class,
            'member_id',
        );
    }
}
