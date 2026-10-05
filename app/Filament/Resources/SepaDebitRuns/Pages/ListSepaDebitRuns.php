<?php

namespace App\Filament\Resources\SepaDebitRuns\Pages;

use App\Application\Sepa\PrepareSepaDebitRun;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Sepa\Models\SepaDebitRun;
use App\Filament\Resources\SepaDebitRuns\SepaDebitRunResource;
use App\Models\User;
use Auth;
use Carbon\CarbonImmutable;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;

class ListSepaDebitRuns extends ListRecords
{
    protected static string $resource = SepaDebitRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prepareSepaDebitRun')
                ->label('Lastschriftlauf vorbereiten')
                ->icon('heroicon-o-banknotes')
                ->requiresConfirmation()
                ->visible(static fn (): bool => Gate::allows('create', SepaDebitRun::class))
                ->schema([
                    TextInput::make('name')
                        ->label('Bezeichnung')
                        ->required()
                        ->maxLength(255),

                    DatePicker::make('collection_date')
                        ->label('Gewünschtes Einzugsdatum')
                        ->required(),
                ])
                ->action(static function (array $data, PrepareSepaDebitRun $service): void {
                    $user = Auth::user();
                    abort_unless($user instanceof User, 403);
                    abort_unless($user->can(Permission::SepaDebitRunsCreate->value), 403);

                    try {
                        $run = $service->handle(
                            name: (string) $data['name'],
                            collectionDate: CarbonImmutable::parse($data['collection_date']),
                            createdBy: $user,
                        );
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->title('Lastschriftlauf konnte nicht vorbereitet werden')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('SEPA-Lastschriftlauf vorbereitet')
                        ->body(sprintf(
                            '%d Positionen, %s €, %d Fehler.',
                            $run->items_count,
                            $run->total_amount,
                            $run->errors_count,
                        ))
                        ->success()
                        ->send();
                }),
        ];
    }
}
