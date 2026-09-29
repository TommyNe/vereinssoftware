<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Club\CurrentClub;
use App\Application\Contribution\CreateContributionCharge;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class CreateContributionChargeAction
{
    public static function make(): Action
    {
        return Action::make(
            'createContributionCharge'
        )
            ->label(
                'Beitragsforderung erstellen'
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
                    ->label(
                        'Beitragsart'
                    )
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

                DatePicker::make(
                    'calculation_date'
                )
                    ->label(
                        'Berechnungsdatum'
                    )
                    ->required()
                    ->default(
                        today()
                    ),

                DatePicker::make(
                    'period_from'
                )
                    ->label(
                        'Zeitraum von'
                    )
                    ->required(),

                DatePicker::make(
                    'period_until'
                )
                    ->label(
                        'Zeitraum bis'
                    )
                    ->afterOrEqual(
                        'period_from'
                    ),

                DatePicker::make(
                    'due_date'
                )
                    ->label(
                        'Fällig am'
                    )
                    ->required(),

                TextInput::make(
                    'description'
                )
                    ->label(
                        'Beschreibung'
                    )
                    ->required()
                    ->maxLength(255),
            ])

            ->action(
                static function (
                    array $data,
                    Member $record,
                    CreateContributionCharge $creator,
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
                        ContributionType::query()
                            ->whereKey(
                                $data[
                                'contribution_type_id'
                                ]
                            )
                            ->where(
                                'club_id',
                                app(
                                    CurrentClub::class
                                )->id()
                            )
                            ->firstOrFail();

                    $creator->handle(
                        member: $record,
                        contributionType: $type,

                        calculationDate: CarbonImmutable::parse(
                            $data[
                            'calculation_date'
                            ]
                        ),

                        periodFrom: CarbonImmutable::parse(
                            $data[
                            'period_from'
                            ]
                        ),

                        periodUntil: filled(
                            $data[
                            'period_until'
                            ] ?? null
                        )
                            ? CarbonImmutable::parse(
                                $data[
                                'period_until'
                                ]
                            )
                            : null,

                        dueDate: CarbonImmutable::parse(
                            $data[
                            'due_date'
                            ]
                        ),

                        description: (string) $data[
                        'description'
                        ],

                        createdBy: $user,
                    );

                    Notification::make()
                        ->title(
                            'Beitragsforderung erstellt'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
