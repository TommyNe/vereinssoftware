<?php

namespace App\Domain\Contribution\Models;

use App\Domain\Club\Models\Club;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentAllocation extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'payment_id',
        'contribution_charge_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' =>
                'decimal:2',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(
            Club::class
        );
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            Payment::class
        );
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(
            ContributionCharge::class,
            'contribution_charge_id'
        );
    }
}
