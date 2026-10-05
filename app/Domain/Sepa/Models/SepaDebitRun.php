<?php

namespace App\Domain\Sepa\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SepaDebitRun extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'status',
        'name',
        'collection_date',
        'items_count',
        'errors_count',
        'total_amount',
        'prepared_at',
        'exported_at',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SepaDebitRunStatus::class,

            'collection_date' => 'immutable_date',

            'total_amount' => 'decimal:2',

            'prepared_at' => 'immutable_datetime',

            'exported_at' => 'immutable_datetime',

            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(
            Club::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            SepaDebitItem::class
        );
    }

    /** @return HasMany<SepaDebitRunError, $this> */
    public function errors(): HasMany
    {
        return $this->hasMany(SepaDebitRunError::class);
    }
}
