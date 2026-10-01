<?php

namespace App\Domain\Contribution\Models;

use App\Domain\Membership\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ContributionRunError extends Model
{
    protected $fillable = [
        'contribution_run_id',
        'member_id',
        'message',
    ];

    /**
     * @return BelongsTo<ContributionRun, $this>
     */
    public function contributionRun(): BelongsTo
    {
        return $this->belongsTo(ContributionRun::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'uuid');
    }
}
