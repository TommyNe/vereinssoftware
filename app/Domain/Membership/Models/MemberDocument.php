<?php

namespace App\Domain\Membership\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Membership\Enums\MemberDocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MemberDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'club_id',
        'member_id',
        'type',
        'original_name',
        'storage_path',
        'mime_type',
        'size',
        'checksum',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' =>
                MemberDocumentType::class,
        ];
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(
            Club::class
        );
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(
            Member::class
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }
}
