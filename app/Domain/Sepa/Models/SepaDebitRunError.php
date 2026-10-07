<?php

namespace App\Domain\Sepa\Models;

use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Membership\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SepaDebitRunError extends Model
{
    protected $fillable = [
        'sepa_debit_run_id',
        'member_id',
        'contribution_charge_id',
        'message',
    ];

    /** @return BelongsTo<SepaDebitRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(SepaDebitRun::class, 'sepa_debit_run_id');
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'uuid');
    }

    /** @return BelongsTo<ContributionCharge, $this> */
    public function charge(): BelongsTo
    {
        return $this->belongsTo(ContributionCharge::class, 'contribution_charge_id');
    }
}
