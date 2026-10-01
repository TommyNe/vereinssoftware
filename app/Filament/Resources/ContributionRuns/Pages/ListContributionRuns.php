<?php

namespace App\Filament\Resources\ContributionRuns\Pages;

use App\Application\Club\CurrentClub;
use App\Application\Contribution\RunContributions;
use App\Domain\Contribution\Models\ContributionRun;
use App\Domain\Contribution\Models\ContributionType;
use App\Filament\Resources\ContributionRuns\ContributionRunResource;
use App\Filament\Resources\ContributionRuns\Schemas\ContributionRunForm;
use App\Models\User;
use Auth;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;

class ListContributionRuns extends ListRecords
{
    protected static string $resource = ContributionRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runContributions')
                ->label('Beitragslauf erstellen')
                ->icon('heroicon-o-play')
                ->schema(ContributionRunForm::components())
                ->action(
                    static function (
                        array $data,
                        RunContributions $runner,
                    ): void {
                        $user = Auth::user();

                        abort_unless(
                            $user instanceof User,
                            403
                        );

                        Gate::authorize(
                            'create',
                            ContributionRun::class
                        );

                        $club =
                            app(
                                CurrentClub::class
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
                                    $club->id()
                                )
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->firstOrFail();

                        $run =
                            $runner->handle(
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
                                'Beitragslauf abgeschlossen'
                            )
                            ->body(
                                sprintf(
                                    '%d Forderungen erstellt, %d beitragsfrei, %d übersprungen, %d Fehler.',
                                    $run->charges_created,
                                    $run->members_exempt,
                                    $run->duplicates_skipped,
                                    $run->errors_count,
                                )
                            )
                            ->success()
                            ->send();
                    }
                )
                ->requiresConfirmation()
                ->modalHeading(
                    'Beitragslauf wirklich starten?'
                )
                ->modalDescription(
                    'Für alle relevanten Mitglieder werden Beitragsforderungen erzeugt. Bereits vorhandene Forderungen werden übersprungen.'
                ),
        ];
    }
}
