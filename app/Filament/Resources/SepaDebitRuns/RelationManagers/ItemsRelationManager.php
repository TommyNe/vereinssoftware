<?php

namespace App\Filament\Resources\SepaDebitRuns\RelationManagers;

use App\Application\Sepa\RecordSepaDebitItemEvent;
use App\Application\Sepa\RefreshSepaDebitRunStatus;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Sepa\Enums\SepaDebitEventType;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Positionen';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('view', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Positionen')
            ->recordTitleAttribute('mandate_reference')
            ->columns([
                TextColumn::make('member.full_name')
                    ->label('Mitglied'),

                TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR', locale: 'de'),

                TextColumn::make('mandate_reference')
                    ->label('Mandat'),

                TextColumn::make('purpose')
                    ->label('Verwendungszweck')
                    ->wrap(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Details')
                    ->authorize(fn (): bool => Gate::allows('view', $this->getOwnerRecord()))
                    ->fillForm([])
                    ->schema([
                        TextEntry::make('member.full_name')
                            ->label('Mitglied'),

                        TextEntry::make('amount')
                            ->label('Betrag')
                            ->money('EUR', locale: 'de'),

                        TextEntry::make('mandate_reference')
                            ->label('Mandat'),

                        TextEntry::make('purpose')
                            ->label('Verwendungszweck'),

                        TextEntry::make('iban')
                            ->label('IBAN')
                            ->placeholder('—')
                            ->formatStateUsing(static function (?string $state): string {
                                if ($state === null) {
                                    return '—';
                                }

                                return '•••• •••• •••• '.substr($state, -4);
                            }),
                    ]),
                Action::make('accept')
                    ->label('Von Bank angenommen')
                    ->visible(static fn (SepaDebitItem $record): bool => $record->status === SepaDebitItemStatus::Submitted)
                    ->authorize(fn (): bool => Gate::allows('view', $this->getOwnerRecord())
                        && (Auth::user()?->can(Permission::SepaDebitItemsFeedbackManage->value) ?? false))
                    ->schema([
                        DateTimePicker::make('occurred_at')
                            ->label('Zeitpunkt')
                            ->required()
                            ->default(now()),
                        TextInput::make('bank_reference')
                            ->label('Bankreferenz')
                            ->maxLength(255),
                    ])
                    ->action(function (
                        array $data,
                        SepaDebitItem $record,
                        RecordSepaDebitItemEvent $service,
                        RefreshSepaDebitRunStatus $refreshRun,
                    ): void {
                        $user = Auth::user();

                        abort_unless($user instanceof User, 403);
                        abort_unless($user->can(Permission::SepaDebitItemsFeedbackManage->value), 403);

                        Gate::authorize('view', $this->getOwnerRecord());

                        $service->handle(
                            item: $record,
                            type: SepaDebitEventType::Accepted,
                            occurredAt: CarbonImmutable::parse($data['occurred_at']),
                            reasonCode: null,
                            reasonText: null,
                            bankReference: filled($data['bank_reference'] ?? null)
                                ? (string) $data['bank_reference']
                                : null,
                            source: 'manual',
                            recordedBy: $user,
                        );

                        $refreshRun->handle($record->run()->firstOrFail());
                    }),
                Action::make('reject')
                    ->label('Von Bank abgelehnt')
                    ->color('danger')
                    ->visible(static fn (SepaDebitItem $record): bool => in_array($record->status, [
                        SepaDebitItemStatus::Submitted,
                        SepaDebitItemStatus::Accepted,
                    ], true))
                    ->authorize(fn (): bool => Gate::allows('view', $this->getOwnerRecord())
                        && (Auth::user()?->can(Permission::SepaDebitItemsFeedbackManage->value) ?? false))
                    ->schema([
                        DateTimePicker::make('occurred_at')
                            ->label('Zeitpunkt')
                            ->required()
                            ->default(now()),
                        TextInput::make('bank_reference')
                            ->label('Bankreferenz')
                            ->maxLength(255),
                        TextInput::make('reason_code')
                            ->label('Bank-Fehlercode')
                            ->maxLength(20),
                        Textarea::make('reason_text')
                            ->label('Grund')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (
                        array $data,
                        SepaDebitItem $record,
                        RecordSepaDebitItemEvent $service,
                        RefreshSepaDebitRunStatus $refreshRun,
                    ): void {
                        $user = Auth::user();

                        abort_unless($user instanceof User, 403);
                        abort_unless($user->can(Permission::SepaDebitItemsFeedbackManage->value), 403);

                        Gate::authorize('view', $this->getOwnerRecord());

                        $service->handle(
                            item: $record,
                            type: SepaDebitEventType::Rejected,
                            occurredAt: CarbonImmutable::parse($data['occurred_at']),
                            reasonCode: filled($data['reason_code'] ?? null)
                                ? (string) $data['reason_code']
                                : null,
                            reasonText: (string) $data['reason_text'],
                            bankReference: filled($data['bank_reference'] ?? null)
                                ? (string) $data['bank_reference']
                                : null,
                            source: 'manual',
                            recordedBy: $user,
                        );

                        $refreshRun->handle($record->run()->firstOrFail());
                    }),
                Action::make('return')
                    ->label('Rücklastschrift erfassen')
                    ->color('danger')
                    ->visible(static fn (SepaDebitItem $record): bool => $record->status === SepaDebitItemStatus::Settled)
                    ->authorize(fn (): bool => Gate::allows('view', $this->getOwnerRecord())
                        && (Auth::user()?->can(Permission::SepaDebitItemsFeedbackManage->value) ?? false))
                    ->schema([
                        DateTimePicker::make('occurred_at')
                            ->label('Rücklastschrift am')
                            ->required(),
                        TextInput::make('reason_code')
                            ->label('Bankcode')
                            ->maxLength(20),
                        Textarea::make('reason_text')
                            ->label('Grund der Rücklastschrift')
                            ->required()
                            ->maxLength(500),
                        TextInput::make('bank_reference')
                            ->label('Bankreferenz')
                            ->maxLength(255),
                    ])
                    ->action(function (
                        array $data,
                        SepaDebitItem $record,
                        RecordSepaDebitItemEvent $service,
                        RefreshSepaDebitRunStatus $refreshRun,
                    ): void {
                        $user = Auth::user();

                        abort_unless($user instanceof User, 403);
                        abort_unless($user->can(Permission::SepaDebitItemsFeedbackManage->value), 403);

                        Gate::authorize('view', $this->getOwnerRecord());

                        $service->handle(
                            item: $record,
                            type: SepaDebitEventType::Returned,
                            occurredAt: CarbonImmutable::parse($data['occurred_at']),
                            reasonCode: filled($data['reason_code'] ?? null)
                                ? (string) $data['reason_code']
                                : null,
                            reasonText: (string) $data['reason_text'],
                            bankReference: filled($data['bank_reference'] ?? null)
                                ? (string) $data['bank_reference']
                                : null,
                            source: 'manual',
                            recordedBy: $user,
                        );

                        $refreshRun->handle($record->run()->firstOrFail());
                    }),
            ]);
    }
}
