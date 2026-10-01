<?php

namespace App\Domain\Contribution\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionRunStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ContributionRun extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'contribution_type_id',
        'status',
        'calculation_date',
        'period_from',
        'period_until',
        'due_date',
        'description',
        'members_processed',
        'charges_created',
        'members_exempt',
        'duplicates_skipped',
        'errors_count',
        'total_amount',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContributionRunStatus::class,

            'calculation_date' => 'immutable_date',

            'period_from' => 'immutable_date',

            'period_until' => 'immutable_date',

            'due_date' => 'immutable_date',

            'total_amount' => 'decimal:2',

            'started_at' => 'immutable_datetime',

            'finished_at' => 'immutable_datetime',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(
            Club::class
        );
    }

    public function contributionType(): BelongsTo
    {
        return $this->belongsTo(
            ContributionType::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function charges(): HasMany
    {
        return $this->hasMany(
            ContributionCharge::class
        );
    }
}
