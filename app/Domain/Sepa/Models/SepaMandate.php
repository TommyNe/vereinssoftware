<?php

namespace App\Domain\Sepa\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaMandateStatus;
use App\Models\User;
use Database\Factories\Domain\Sepa\Models\SepaMandateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SepaMandate extends Model
{
    /** @use HasFactory<SepaMandateFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'club_id',
        'member_id',
        'status',
        'mandate_reference',
        'account_holder',
        'iban',
        'bic',
        'signed_at',
        'valid_from',
        'revoked_at',
        'revocation_reason',
        'created_by',
        'revoked_by',
    ];

    protected static function newFactory(): SepaMandateFactory
    {
        return SepaMandateFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => SepaMandateStatus::class,

            'iban' => 'encrypted',

            'bic' => 'encrypted',

            'signed_at' => 'immutable_date',

            'valid_from' => 'immutable_date',

            'revoked_at' => 'immutable_datetime',
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
            'uuid',
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revoked_by'
        );
    }
}
