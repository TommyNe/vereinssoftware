<?php

namespace App\Domain\Sepa\Models;

use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SepaDebitItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'sepa_debit_run_id',
        'contribution_charge_id',
        'member_id',
        'sepa_mandate_id',
        'status',
        'amount',
        'purpose',
        'account_holder',
        'iban',
        'bic',
        'mandate_reference',
        'mandate_signed_at',
        'end_to_end_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SepaDebitItemStatus::class,

            'amount' => 'decimal:2',

            'iban' => 'encrypted',

            'bic' => 'encrypted',

            'mandate_signed_at' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<SepaDebitRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(
            SepaDebitRun::class,
            'sepa_debit_run_id'
        );
    }

    /** @return BelongsTo<ContributionCharge, $this> */
    public function charge(): BelongsTo
    {
        return $this->belongsTo(
            ContributionCharge::class,
            'contribution_charge_id'
        );
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(
            Member::class,
            'member_id',
        );
    }

    /** @return BelongsTo<SepaMandate, $this> */
    public function mandate(): BelongsTo
    {
        return $this->belongsTo(
            SepaMandate::class,
            'sepa_mandate_id'
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            SepaDebitItemEvent::class,
            'sepa_debit_item_id'
        );
    }
}
