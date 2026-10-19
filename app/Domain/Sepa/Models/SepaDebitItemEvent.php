<?php

namespace App\Domain\Sepa\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Sepa\Enums\SepaDebitEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SepaDebitItemEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'sepa_debit_item_id',
        'type',
        'occurred_at',
        'reason_code',
        'reason_text',
        'bank_reference',
        'source',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => SepaDebitEventType::class,

            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(
            Club::class
        );
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            SepaDebitItem::class,
            'sepa_debit_item_id'
        );
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recorded_by'
        );
    }
}
