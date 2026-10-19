<?php

namespace App\Filament\Resources\SepaDebitRuns\Pages;

use App\Application\Sepa\ExportSepaDebitRun;
use App\Application\Sepa\SubmitSepaDebitRun;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Enums\SepaSubmissionMethod;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Filament\Resources\SepaDebitRuns\SepaDebitRunResource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/** @method SepaDebitRun getRecord() */
class ViewSepaDebitRun extends ViewRecord
{
    protected static string $resource = SepaDebitRunResource::class;

    public function getTitle(): string
    {
        return (string) $this->getRecord()->getAttribute('name');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('exportXml')
                ->label('SEPA-XML erstellen')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->visible(
                    fn (): bool => $this->getRecord()->status
                        === SepaDebitRunStatus::Prepared
                        && Gate::allows(
                            'export',
                            $this->getRecord(),
                        )
                )
                ->requiresConfirmation()
                ->modalHeading(
                    'SEPA-XML-Datei erstellen?'
                )
                ->modalDescription(
                    'Die Lastschriftpositionen werden geprüft und '
                    .'als unveränderliche Exportdatei gespeichert.'
                )
                ->action(function (
                    ExportSepaDebitRun $exporter,
                ): void {
                    $run = $this->getRecord();

                    Gate::authorize('export', $run);

                    $exporter->handle($run);

                    Notification::make()
                        ->title('SEPA-XML erfolgreich erstellt')
                        ->success()
                        ->send();

                    $run->refresh();
                }),

            Action::make('downloadXml')
                ->label('XML herunterladen')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(
                    fn (): bool => $this->getRecord()->status
                        === SepaDebitRunStatus::Exported
                        && Gate::allows(
                            'export',
                            $this->getRecord(),
                        )
                )
                ->url(
                    fn (): string => route(
                        'sepa-debit-runs.download',
                        ['run' => $this->getRecord()->getKey()],
                    )
                )
                ->openUrlInNewTab(),

            Action::make('markSubmitted')
                ->label('Als eingereicht markieren')
                ->icon('heroicon-o-paper-airplane')
                ->visible(
                    fn (): bool => $this->getRecord()->status
                        === SepaDebitRunStatus::Exported
                        && Gate::allows('submit', $this->getRecord())
                )
                ->requiresConfirmation()
                ->schema([
                    Select::make('submission_method')
                        ->label('Einreichungsweg')
                        ->required()
                        ->options(
                            collect(SepaSubmissionMethod::cases())
                                ->mapWithKeys(static fn (SepaSubmissionMethod $method): array => [
                                    $method->value => $method->label(),
                                ])
                                ->all()
                        ),
                    DateTimePicker::make('submitted_at')
                        ->label('Eingereicht am')
                        ->required()
                        ->default(now()),
                    TextInput::make('bank_reference')
                        ->label('Bank-/Upload-Referenz')
                        ->maxLength(255),
                ])
                ->action(function (array $data, SubmitSepaDebitRun $service): void {
                    $user = Auth::user();

                    abort_unless($user instanceof User, 403);

                    $run = $this->getRecord();

                    Gate::authorize('submit', $run);

                    $service->handle(
                        run: $run,
                        method: SepaSubmissionMethod::from((string) $data['submission_method']),
                        submittedAt: CarbonImmutable::parse($data['submitted_at']),
                        bankReference: filled($data['bank_reference'] ?? null)
                            ? (string) $data['bank_reference']
                            : null,
                        submittedBy: $user,
                    );

                    Notification::make()
                        ->title('Lastschriftlauf als eingereicht markiert')
                        ->success()
                        ->send();

                    $this->getRecord()->refresh();
                }),
        ];
    }
}
