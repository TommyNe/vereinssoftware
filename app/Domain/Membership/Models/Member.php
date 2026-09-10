<?php

namespace App\Domain\Membership\Models;

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use Database\Factories\Domain\Membership\Models\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EventSourcing\Projections\Projection;

final class Member extends Projection
{
    use HasFactory;

    protected $table = 'members';

    protected $guarded = [];

    protected static function newFactory(): MemberFactory
    {
        return MemberFactory::new();
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'joined_at' => 'date',
            'left_at' => 'date',
        ];
    }

    public function scopeForCurrentClub(
        Builder $query
    ): Builder {
        $currentClub = app(CurrentClub::class);

        return $query->where(
            'club_id',
            $currentClub->id(),
        );
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
