<?php

namespace App\Domain\Club\Models;

use App\Domain\Membership\Models\ClubFunction;
use App\Domain\Membership\Models\Department;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MembershipType;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'short_name',
        'email',
        'phone',
        'street',
        'postal_code',
        'city',
    ];

    /**
     * @return HasMany<Member, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'club_user'
        )->withTimestamps();
    }

    /**
     * @return HasMany<MembershipType, $this>
     */
    public function membershipTypes(): HasMany
    {
        return $this->hasMany(
            MembershipType::class
        );
    }

    /**
     * @return HasMany<Department, $this>
     */
    public function departments(): HasMany
    {
        return $this->hasMany(
            Department::class
        );
    }

    /**
     * @return HasMany<ClubFunction, $this>
     */
    public function clubFunctions(): HasMany
    {
        return $this->hasMany(
            ClubFunction::class
        );
    }

    /**
     * @return HasMany<ClubInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(
            ClubInvitation::class
        );
    }
}
