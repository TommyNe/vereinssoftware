<?php

namespace App\Application\Audit;

use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\MemberContributionOverride;
use App\Domain\Membership\Models\ClubFunction;
use App\Domain\Membership\Models\Department;
use App\Domain\Membership\Models\MemberDocument;
use App\Domain\Membership\Models\MembershipType;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use LogicException;

final readonly class ModelAuditObserver
{
    public function __construct(private AuditLogger $audit) {}

    public function created(Model $model): void
    {
        $actorId = $model->getAttribute('created_by') ?? $model->getAttribute('uploaded_by');

        $this->audit->log(
            $model instanceof MemberDocument ? AuditAction::DocumentUploaded : $this->action($model, 'created'),
            $model,
            $actorId === null ? null : User::query()->find($actorId),
            [
                'club_id' => $model->getAttribute('club_id'),
                'new' => $this->snapshot($model, $this->attributes($model)),
            ],
        );
    }

    public function updated(Model $model): void
    {
        $changes = Arr::only($model->getChanges(), $this->attributes($model));

        if ($changes === []) {
            return;
        }

        $this->audit->log($this->action($model, 'updated'), $model, properties: [
            'club_id' => $model->getAttribute('club_id'),
            'old' => $this->snapshot($model, array_keys($changes), true),
            'new' => $this->snapshot($model, array_keys($changes)),
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->audit->log($this->action($model, 'deleted'), $model, properties: [
            'club_id' => $model->getAttribute('club_id'),
            'old' => $this->snapshot($model, $this->attributes($model)),
        ]);
    }

    /**
     * @param  list<string>  $attributes
     * @return array<string, mixed>
     */
    private function snapshot(Model $model, array $attributes, bool $original = false): array
    {
        $values = [];

        foreach ($attributes as $attribute) {
            if (! array_key_exists($attribute, $model->getAttributes())) {
                continue;
            }

            $value = $original ? $model->getOriginal($attribute) : $model->getAttribute($attribute);
            $values[$attribute] = match (true) {
                $value instanceof BackedEnum => $value->value,
                $value instanceof DateTimeInterface => $value->format('Y-m-d'),
                default => $value,
            };
        }

        return $values;
    }

    private function action(Model $model, string $operation): AuditAction
    {
        $prefix = match ($model::class) {
            ContributionType::class => 'contribution.type',
            ContributionRate::class => 'contribution.rate',
            MemberContributionOverride::class => 'member.contribution-override',
            MemberDocument::class => 'member.document',
            MembershipType::class => 'membership-type',
            Department::class => 'department',
            ClubFunction::class => 'club-function',
            default => throw new LogicException('Für dieses Modell ist kein Audit definiert.'),
        };

        return AuditAction::from($prefix.'.'.$operation);
    }

    /**
     * @return list<string>
     */
    private function attributes(Model $model): array
    {
        return match ($model::class) {
            ContributionType::class => ['code', 'name', 'description', 'interval', 'sort_order', 'is_active'],
            ContributionRate::class => ['contribution_type_id', 'membership_type_id', 'amount', 'valid_from', 'valid_until', 'is_active'],
            MemberContributionOverride::class => ['member_id', 'contribution_type_id', 'type', 'amount', 'valid_from', 'valid_until', 'reason', 'is_active'],
            MemberDocument::class => ['member_id', 'type', 'original_name', 'mime_type', 'size', 'checksum', 'uploaded_by'],
            MembershipType::class, Department::class, ClubFunction::class => ['name', 'description', 'sort_order', 'is_active'],
            default => throw new LogicException('Für dieses Modell ist kein Audit definiert.'),
        };
    }
}
