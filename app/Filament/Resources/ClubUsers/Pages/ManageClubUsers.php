<?php

namespace App\Filament\Resources\ClubUsers\Pages;

use App\Application\Club\ClubUserManager;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use App\Filament\Resources\ClubUsers\ClubUserResource;
use App\Models\User;
use Auth;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageClubUsers extends ManageRecords
{
    protected static string $resource = ClubUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addUser')
                ->label('Benutzer hinzufügen')
                ->icon(
                    'heroicon-o-user-plus'
                )

                ->visible(
                    static fn (): bool => Auth::user()?->can(
                        Permission::ClubUsersManage
                            ->value
                    ) ?? false
                )

                ->modalHeading(
                    'Benutzer zum Verein hinzufügen'
                )

                ->schema([
                    TextInput::make('email')
                        ->label('E-Mail-Adresse')
                        ->email()
                        ->required()
                        ->maxLength(255),

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
                                        $role->value => $role->label(),
                                    ]
                                )
                                ->all()
                        ),
                ])

                ->action(
                    static function (
                        array $data,
                        ClubUserManager $manager,
                    ): void {
                        abort_unless(
                            Auth::user()?->can(
                                Permission::ClubUsersManage
                                    ->value
                            ),
                            403,
                        );

                        $user = User::query()
                            ->where(
                                'email',
                                mb_strtolower(
                                    trim(
                                        $data['email']
                                    )
                                )
                            )
                            ->first();

                        if (! $user instanceof User) {
                            Notification::make()
                                ->title(
                                    'Benutzer nicht gefunden'
                                )
                                ->body(
                                    'Für diese E-Mail-Adresse existiert noch kein Benutzerkonto.'
                                )
                                ->warning()
                                ->send();

                            return;
                        }

                        $role = Role::tryFrom(
                            (string) $data['role']
                        );

                        abort_if(
                            $role === null,
                            422,
                            'Ungültige Rolle.'
                        );

                        $manager->addUser(
                            user: $user,
                            role: $role,
                        );

                        Notification::make()
                            ->title(
                                'Benutzer hinzugefügt'
                            )
                            ->success()
                            ->send();
                    }
                ),
        ];
    }
}
