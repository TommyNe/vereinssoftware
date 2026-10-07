<?php

namespace App\Domain\Sepa\Models;

use App\Domain\Club\Models\Club;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClubSepaConfiguration extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'creditor_identifier',
        'account_holder',
        'iban',
        'bic',
        'mandate_reference_prefix',
        'default_lead_days',
        'default_purpose',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'iban' => 'encrypted',

            'bic' => 'encrypted',

            'default_lead_days' => 'integer',

            'is_active' => 'boolean',
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

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}
