<?php

namespace App\Filament\Resources\SepaDebitRuns\Pages;

use App\Application\Sepa\ExportSepaDebitRun;
use App\Domain\Sepa\Enums\SepaDebitRunStatus;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Filament\Resources\SepaDebitRuns\SepaDebitRunResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
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
        ];
    }
}
