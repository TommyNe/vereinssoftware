<?php

namespace App\Domain\Contribution\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionInterval;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ContributionType extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'name',
        'description',
        'interval',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'interval' => ContributionInterval::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(
            Club::class
        );
    }

    /**
     * @return HasMany<ContributionRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(
            ContributionRate::class
        );
    }

    /**
     * @return HasMany<ContributionRate, $this>
     */
    public function contributionRates(): HasMany
    {
        return $this->hasMany(
            ContributionRate::class
        );
    }

    public function charges(): HasMany
    {
        return $this->hasMany(
            ContributionCharge::class
        );
    }
}
