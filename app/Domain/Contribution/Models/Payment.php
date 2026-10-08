<?php

namespace App\Domain\Contribution\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Contribution\Enums\PaymentMethod;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'member_id',
        'status',
        'method',
        'amount',
        'currency',
        'booking_date',
        'value_date',
        'reference',
        'notes',
        'source_type',
        'source_id',
        'reversed_at',
        'reversal_reason',
        'created_by',
        'reversed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,

            'method' => PaymentMethod::class,

            'amount' => 'decimal:2',

            'booking_date' => 'immutable_date',

            'value_date' => 'immutable_date',

            'reversed_at' => 'immutable_datetime',
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
            Member::class,
            'member_id',
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reversed_by'
        );
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(
            PaymentAllocation::class
        );
    }
}
