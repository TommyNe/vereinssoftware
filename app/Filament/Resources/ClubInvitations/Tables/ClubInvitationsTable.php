<?php

namespace App\Filament\Resources\ClubInvitations\Tables;

use App\Application\Club\ClubInvitationManager;
use App\Domain\Club\Models\ClubInvitation;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use Auth;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ClubInvitationsTable
{
    public static function configure(
        Table $table,
    ): Table {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Rolle')
                    ->formatStateUsing(
                        static fn (
                            string $state
                        ): string => Role::tryFrom($state)
                            ?->label()
                            ?? $state
                    )
                    ->badge(),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(
                        static fn (
                            ClubInvitation $record
                        ): string => $record
                            ->status()
                            ->label()
                    )
                    ->badge()
                    ->color(
                        static fn (
                            ClubInvitation $record
                        ): string => $record
                            ->status()
                            ->color()
                    ),

                TextColumn::make('expires_at')
                    ->label('Läuft ab')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('inviter.name')
                    ->label('Eingeladen von')
                    ->placeholder('–'),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->defaultSort(
                'created_at',
                'desc'
            )
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Offen',

                        'accepted' => 'Akzeptiert',

                        'revoked' => 'Widerrufen',

                        'expired' => 'Abgelaufen',
                    ])
                    ->query(
                        static function (
                            Builder $query,
                            array $data,
                        ): Builder {
                            return match (
                                $data['value']
                                ?? null
                            ) {
                                'pending' => $query
                                    ->whereNull(
                                        'accepted_at'
                                    )
                                    ->whereNull(
                                        'revoked_at'
                                    )
                                    ->where(
                                        'expires_at',
                                        '>',
                                        now()
                                    ),

                                'accepted' => $query
                                    ->whereNotNull(
                                        'accepted_at'
                                    ),

                                'revoked' => $query
                                    ->whereNotNull(
                                        'revoked_at'
                                    ),

                                'expired' => $query
                                    ->whereNull(
                                        'accepted_at'
                                    )
                                    ->whereNull(
                                        'revoked_at'
                                    )
                                    ->where(
                                        'expires_at',
                                        '<=',
                                        now()
                                    ),

                                default => $query,
                            };
                        }
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::resendAction(),
                    self::revokeAction(),
                ]),
            ]);
    }

    private static function resendAction(): Action
    {
        return Action::make('resend')
            ->label('Erneut senden')
            ->icon(
                'heroicon-o-paper-airplane'
            )

            ->visible(
                static function (
                    ClubInvitation $record
                ): bool {
                    return (
                        Auth::user()?->can(
                            Permission::ClubUsersManage
                                ->value
                        ) ?? false
                    )
                        && ! $record->isAccepted()
                        && ! $record->isRevoked();
                }
            )

            ->requiresConfirmation()

            ->modalHeading(
                'Einladung erneut senden'
            )

            ->modalDescription(
                'Der bisherige Einladungslink wird ungültig. '
                .'Es wird ein neuer Link mit neuer Gültigkeitsdauer erstellt.'
            )

            ->modalSubmitActionLabel(
                'Erneut senden'
            )

            ->action(
                static function (
                    ClubInvitation $record,
                    ClubInvitationManager $manager,
                ): void {
                    abort_unless(
                        Auth::user()?->can(
                            Permission::ClubUsersManage
                                ->value
                        ),
                        403,
                    );
                    $manager->resend(
                        $record
                    );

                    Notification::make()
                        ->title(
                            'Einladung erneut versendet'
                        )
                        ->success()
                        ->send();
                }
            );
    }

    private static function revokeAction(): Action
    {
        return Action::make('revoke')
            ->label('Widerrufen')
            ->icon('heroicon-o-x-circle')
            ->color('danger')

            ->visible(
                static function (
                    ClubInvitation $record
                ): bool {
                    return (
                        Auth::user()?->can(
                            Permission::ClubUsersManage
                                ->value
                        ) ?? false
                    )
                        && ! $record->isAccepted()
                        && ! $record->isRevoked();
                }
            )

            ->requiresConfirmation()

            ->modalHeading(
                'Einladung widerrufen'
            )

            ->modalDescription(
                'Der Einladungslink wird sofort ungültig.'
            )

            ->modalSubmitActionLabel(
                'Einladung widerrufen'
            )

            ->action(
                static function (
                    ClubInvitation $record,
                    ClubInvitationManager $manager,
                ): void {
                    abort_unless(
                        Auth::user()?->can(
                            Permission::ClubUsersManage
                                ->value
                        ),
                        403,
                    );
                    $manager->revoke(
                        $record
                    );

                    Notification::make()
                        ->title(
                            'Einladung widerrufen'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
