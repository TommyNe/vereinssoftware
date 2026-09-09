<?php

namespace App\Domain\Club\Models;

use App\Domain\Membership\Models\Member;
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
        'city'
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'club_user'
        )->withTimestamps();
    }
}
