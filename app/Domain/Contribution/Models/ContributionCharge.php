<?php

namespace App\Domain\Contribution\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContributionCharge extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'member_id',
        'contribution_type_id',
        'contribution_run_id',
        'status',
        'amount',
        'description',
        'period_from',
        'period_until',
        'due_date',
        'paid_at',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContributionChargeStatus::class,

            'amount' => 'decimal:2',

            'period_from' => 'immutable_date',

            'period_until' => 'immutable_date',

            'due_date' => 'immutable_date',

            'paid_at' => 'immutable_datetime',

            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(
            Club::class
        );
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(
            Member::class
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

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }

    public function isOverdue(): bool
    {
        return
            $this->status
            === ContributionChargeStatus::Open
            && $this->due_date->isPast();
    }

    public function contributionRun(): BelongsTo
    {
        return $this->belongsTo(
            ContributionRun::class
        );
    }
}
