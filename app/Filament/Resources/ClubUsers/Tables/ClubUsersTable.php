<?php

namespace App\Filament\Resources\ClubUsers\Tables;

use App\Application\Club\ClubUserManager;
use App\Application\Club\CurrentClub;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use Auth;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ClubUsersTable
{
    public static function configure(
        Table $table,
    ): Table {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('club_roles')
                    ->label('Rolle')
                    ->state(
                        static function (
                            User $record
                        ): string {
                            $club = app(
                                CurrentClub::class
                            );

                            setPermissionsTeamId(
                                $club->id()
                            );

                            $record->unsetRelation(
                                'roles'
                            );

                            return $record
                                ->getRoleNames()
                                ->map(
                                    static function (
                                        string $role
                                    ): string {
                                        return Role::tryFrom($role)
                                            ?->label()
                                            ?? $role;
                                    }
                                )
                                ->implode(', ');
                        }
                    )
                    ->badge(),
            ])

            ->recordActions([
                Action::make('changeRole')
                    ->label('Rolle ändern')
                    ->icon('heroicon-o-shield-check')

                    ->visible(
                        static fn (): bool => Auth::user()?->can(
                            Permission::ClubUsersManage
                                ->value
                        ) ?? false
                    )

                    ->fillForm(
                        static function (
                            User $record,
                        ): array {
                            $club = app(
                                CurrentClub::class
                            );

                            setPermissionsTeamId(
                                $club->id()
                            );

                            $record->unsetRelation(
                                'roles'
                            );

                            return [
                                'role' => $record
                                    ->getRoleNames()
                                    ->first(),
                            ];
                        }
                    )

                    ->schema([
                        Select::make('role')
                            ->label('Rolle')
                            ->required()
                            ->options(
                                collect(
                                    Role::cases()
                                )
                                    ->mapWithKeys(
                                        static fn (
                                            Role $role
                                        ): array => [
                                            $role->value => match ($role) {
                                                Role::Administrator => 'Administrator',

                                                Role::Board => 'Vorstand',

                                                Role::Treasure => 'Kassenwart',

                                                Role::ShootingManager => 'Schießwart',

                                                Role::Member => 'Mitglied',
                                            },
                                        ]
                                    )
                                    ->all()
                            ),
                    ])

                    ->action(
                        static function (
                            array $data,
                            User $record,
                            ClubUserManager $manager,
                        ): void {
                            $currentUser =
                                Auth::user();

                            abort_unless(
                                $currentUser?->can(
                                    Permission::ClubUsersManage
                                        ->value
                                ),
                                403,
                            );

                            $role = Role::tryFrom(
                                (string) $data['role']
                            );

                            abort_if(
                                $role === null,
                                422,
                                'Ungültige Rolle.'
                            );

                            $manager->changeRole(
                                user: $record,
                                role: $role,
                            );

                            Notification::make()
                                ->title(
                                    'Rolle geändert'
                                )
                                ->success()
                                ->send();
                        }
                    ),

                Action::make('removeFromClub')
                    ->label('Aus Verein entfernen')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->disabled(
                        static fn (
                            User $record
                        ): bool => Auth::id() ===
                            $record->getKey()
                    )
                    ->modalHeading(
                        'Benutzer aus Verein entfernen'
                    )

                    ->modalDescription(
                        'Der Benutzer verliert den Zugriff auf diesen Verein. '
                        .'Sein globales Benutzerkonto bleibt bestehen.'
                    )

                    ->visible(
                        static fn (): bool => Auth::user()?->can(
                            Permission::ClubUsersManage
                                ->value
                        ) ?? false
                    )

                    ->action(
                        static function (
                            User $record,
                            ClubUserManager $manager,
                        ): void {
                            abort_unless(
                                Auth::user()?->can(
                                    Permission::ClubUsersManage
                                        ->value
                                ),
                                403,
                            );

                            $manager->removeUser(
                                $record
                            );

                            Notification::make()
                                ->title(
                                    'Benutzer aus Verein entfernt'
                                )
                                ->success()
                                ->send();
                        }
                    ),
            ]);
    }
}
