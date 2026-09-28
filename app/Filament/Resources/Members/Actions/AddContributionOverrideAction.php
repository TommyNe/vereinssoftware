<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Club\CurrentClub;
use App\Application\Contribution\MemberContributionOverrideManager;
use App\Domain\Contribution\Enums\MemberContributionOverrideType;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Auth;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

final class AddContributionOverrideAction
{
    public static function make(): Action
    {
        return Action::make(
            'addContributionOverride'
        )
            ->label(
                'Beitragsregel hinzufügen'
            )
            ->icon(
                'heroicon-o-banknotes'
            )
            ->visible(
                static fn (
                    Member $record
                ): bool => Gate::allows(
                    'manageContributions',
                    $record
                )
            )
            ->schema([
                Select::make(
                    'contribution_type_id'
                )
                    ->label('Beitragsart')
                    ->required()
                    ->options(
                        static fn (): array => ContributionType::query()
                            ->where(
                                'club_id',
                                app(
                                    CurrentClub::class
                                )->id()
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->orderBy(
                                'sort_order'
                            )
                            ->orderBy(
                                'name'
                            )
                            ->pluck(
                                'name',
                                'id'
                            )
                            ->all()
                    ),

                Select::make('type')
                    ->label(
                        'Art der Regel'
                    )
                    ->required()
                    ->live()
                    ->options(
                        collect(
                            MemberContributionOverrideType::cases()
                        )
                            ->mapWithKeys(
                                static fn (
                                    MemberContributionOverrideType $type
                                ): array => [
                                    $type->value => $type->label(),
                                ]
                            )
                            ->all()
                    ),

                TextInput::make('amount')
                    ->label(
                        'Individueller Betrag'
                    )
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->suffix('€')
                    ->visible(
                        static fn ($get): bool => $get('type')
                            === MemberContributionOverrideType::FixedAmount->value
                    )
                    ->required(
                        static fn ($get): bool => $get('type')
                            === MemberContributionOverrideType::FixedAmount->value
                    ),

                DatePicker::make(
                    'valid_from'
                )
                    ->label('Gültig ab')
                    ->required()
                    ->default(today()),

                DatePicker::make(
                    'valid_until'
                )
                    ->label('Gültig bis')
                    ->afterOrEqual(
                        'valid_from'
                    ),

                Textarea::make('reason')
                    ->label('Begründung')
                    ->rows(3)
                    ->maxLength(500),
            ])
            ->action(
                static function (
                    array $data,
                    Member $record,
                    MemberContributionOverrideManager $manager,
                ): void {
                    Gate::authorize(
                        'manageContributions',
                        $record
                    );

                    $user =
                        Auth::user();

                    abort_unless(
                        $user instanceof User,
                        403
                    );

                    $type =
                        MemberContributionOverrideType::from(
                            (string) $data['type']
                        );

                    $manager->create(
                        member: $record,
                        contributionTypeId: (string) $data[
                        'contribution_type_id'
                        ],
                        type: $type,
                        amount: isset(
                            $data['amount']
                        )
                            ? (string) $data[
                        'amount'
                        ]
                            : null,
                        validFrom: CarbonImmutable::parse(
                            $data[
                            'valid_from'
                            ]
                        ),
                        validUntil: isset(
                            $data[
                            'valid_until'
                            ]
                        )
                        && $data[
                        'valid_until'
                        ] !== null
                            ? CarbonImmutable::parse(
                                $data[
                                'valid_until'
                                ]
                            )
                            : null,
                        reason: isset(
                            $data['reason']
                        )
                            ? (string) $data[
                        'reason'
                        ]
                            : null,
                        createdBy: $user,
                    );

                    Notification::make()
                        ->title(
                            'Beitragsregel gespeichert'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
