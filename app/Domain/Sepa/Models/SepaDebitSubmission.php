<?php

namespace App\Domain\Sepa\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Sepa\Enums\SepaDebitSubmissionStatus;
use App\Domain\Sepa\Enums\SepaSubmissionMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SepaDebitSubmission extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'sepa_debit_run_id',
        'status',
        'submission_method',
        'bank_reference',
        'submitted_at',
        'bank_responded_at',
        'bank_message',
        'submitted_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SepaDebitSubmissionStatus::class,

            'submission_method' => SepaSubmissionMethod::class,

            'submitted_at' => 'immutable_datetime',

            'bank_responded_at' => 'immutable_datetime',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(
            Club::class
        );
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(
            SepaDebitRun::class,
            'sepa_debit_run_id'
        );
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'submitted_by'
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
