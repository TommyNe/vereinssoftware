<?php

namespace App\Domain\Membership\Models;

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Membership\Enums\MembershipStatus;
use Carbon\CarbonImmutable;
use Database\Factories\Domain\Membership\Models\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EventSourcing\Projections\Projection;

/**
 * @property string $id
 * @property string $club_id
 * @property string|null $membership_type_id
 * @property string $first_name
 * @property string $last_name
 * @property MembershipStatus $status
 * @property CarbonImmutable|null $birth_date
 * @property CarbonImmutable|null $joined_at
 * @property CarbonImmutable|null $left_at
 * @property-read string $full_name
 */
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

    /**
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function scopeForCurrentClub(
        Builder $query
    ): Builder {
        $currentClub = app(
            CurrentClub::class
        );

        if (! $currentClub->hasClub()) {
            return $query->whereRaw('1 = 0');
        }

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

    /**
     * @return HasMany<MemberFunction, $this>
     */
    public function functionAssignments(): HasMany
    {
        return $this
            ->hasMany(
                MemberFunction::class,
                'member_id',
            )
            ->orderByDesc('valid_from');
    }

    /**
     * @return HasMany<MemberFunction, $this>
     */
    public function activeFunctionAssignments(): HasMany
    {
        return $this
            ->hasMany(
                MemberFunction::class,
                'member_id',
            )
            ->whereNull('valid_until');
    }

    public function currentFunctions(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                ClubFunction::class,
                'member_functions',
                'member_id',
                'club_function_id',
            )
            ->wherePivotNull('valid_until')
            ->withPivot([
                'valid_from',
                'valid_until',
            ]);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(
                "{$this->first_name} {$this->last_name}"
            ),
        );
    }
}
