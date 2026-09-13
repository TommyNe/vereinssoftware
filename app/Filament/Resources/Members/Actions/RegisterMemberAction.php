<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\RegisterMember;
use App\Application\Membership\Handlers\RegisterMemberHandler;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class RegisterMemberAction
{
    public static function make(): Action
    {
        return Action::make('registerMember')
            ->label('Mitglied anlegen')
            ->icon('heroicon-o-user-plus')
            ->color('primary')
            ->visible(
                static function (): bool {
                    $user = Auth::user();

                    return $user instanceof User
                        && $user->can(
                            Permission::MembersCreate->value
                        );
                }
            )
            ->modalHeading('Neues Mitglied anlegen')
            ->modalWidth('2xl')
            ->modalDescription(
                'Die Aufnahme wird über den Event-Sourcing-Prozess gespeichert.'
            )
            ->schema([
                Section::make('Mitgliedschaft')
                    ->schema([
                        TextInput::make('member_number')
                            ->label('Mitgliedsnummer')
                            ->required()
                            ->maxLength(50)
                            ->rules(
                                static function (): array {
                                    $currentClub = app(
                                        CurrentClub::class
                                    );

                                    return [
                                        Rule::unique(
                                            'members',
                                            'member_number'
                                        )->where(
                                            'club_id',
                                            $currentClub->id()
                                        ),
                                    ];
                                }
                            ),
                        DatePicker::make('joined_at')
                            ->label('Eintrittsdatum')
                            ->required()
                            ->native(false)
                            ->default(now()),
                    ])->columns(2),
                Section::make('Persönliche Daten')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Vorname')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('last_name')
                            ->label('Nachname')
                            ->required()
                            ->maxLength(150),

                        DatePicker::make('birth_date')
                            ->label('Geburtsdatum')
                            ->native(false)
                            ->maxDate(now())
                            ->nullable(),


                    ])->columns(2),
            ])
            ->action(
                static function (
                    array                 $data,
                    RegisterMemberHandler $handler,
                ): void {
                    $memberId = (string)Str::uuid();
                    $handler->handle(
                        new RegisterMember(
                            memberId: $memberId,

                            memberNumber: trim($data['member_number']),

                            firstName: trim($data['first_name']),

                            lastName: trim($data['last_name']),

                            birthDate: filled(
                                $data['birth_date'] ?? null
                            )
                                ? CarbonImmutable::parse(
                                    $data['birth_date']
                                )
                                : null,

                            joinedAt: CarbonImmutable::parse(
                                $data['joined_at']
                            ),
                        )
                    );

                    Notification::make()
                        ->title('Mitglied angelegt')
                        ->body(
                            'Das Mitglied wurde erfolgreich aufgenommen.'
                        )
                        ->success()
                        ->send();
                }
            );
    }
}
