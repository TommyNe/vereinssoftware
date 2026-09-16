<?php

namespace App\Filament\Resources\Members\Actions;

use App\Application\Club\CurrentClub;
use App\Application\Membership\RegisterCompleteMember;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Membership\Models\ClubFunction;
use App\Domain\Membership\Models\Department;
use App\Domain\Membership\Models\MembershipType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Wizard\Step;
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
            ->modalWidth('5xl')
            ->modalDescription(
                'Die Aufnahme wird über den Event-Sourcing-Prozess gespeichert.'
            )
            ->steps([
                Step::make('Mitgliedschaft')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('member_number')
                            ->label('Mitgliedsnummer')
                            ->required()
                            ->maxLength(50)
                            ->rules(
                                static function (): array {
                                    return [
                                        Rule::unique(
                                            'members',
                                            'member_number',
                                        )->where(
                                            'club_id',
                                            app(
                                                CurrentClub::class
                                            )->id(),
                                        ),
                                    ];
                                }
                            ),

                        DatePicker::make('joined_at')
                            ->label('Eintrittsdatum')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Select::make('membership_type_id')
                            ->label('Mitgliedsart')
                            ->searchable()
                            ->preload()
                            ->options(
                                static function (): array {
                                    $club = app(
                                        CurrentClub::class
                                    );

                                    return MembershipType::query()
                                        ->where(
                                            'club_id',
                                            $club->id(),
                                        )
                                        ->where(
                                            'is_active',
                                            true,
                                        )
                                        ->orderBy('sort_order')
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all();
                                }
                            )
                            ->nullable(),
                    ])
                    ->columns(2),
                Step::make('Persönliche Daten')
                    ->icon('heroicon-o-user')
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
                    ])
                    ->columns(2),
                Step::make('Adresse')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('street')
                            ->label('Straße')
                            ->maxLength(150),

                        TextInput::make('house_number')
                            ->label('Hausnummer')
                            ->maxLength(20),

                        TextInput::make('postal_code')
                            ->label('PLZ')
                            ->maxLength(20),

                        TextInput::make('city')
                            ->label('Ort')
                            ->maxLength(150),

                        TextInput::make('country_code')
                            ->label('Land')
                            ->default('DE')
                            ->length(2)
                            ->maxLength(2),
                    ])
                    ->columns(2),

                Step::make('Kontakt')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('mobile')
                            ->label('Mobil')
                            ->tel()
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Step::make('Abteilungen')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Select::make('department_ids')
                            ->label('Abteilungen')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(
                                static function (): array {
                                    $club = app(
                                        CurrentClub::class
                                    );

                                    return Department::query()
                                        ->where(
                                            'club_id',
                                            $club->id(),
                                        )
                                        ->where(
                                            'is_active',
                                            true,
                                        )
                                        ->orderBy('sort_order')
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all();
                                }
                            ),
                    ]),

                Step::make('Vereinsfunktionen')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Repeater::make('functions')
                            ->label('Funktionen')
                            ->schema([
                                Select::make('club_function_id')
                                    ->label('Funktion')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(
                                        static function (): array {
                                            $club = app(
                                                CurrentClub::class
                                            );

                                            return ClubFunction::query()
                                                ->where(
                                                    'club_id',
                                                    $club->id(),
                                                )
                                                ->where(
                                                    'is_active',
                                                    true,
                                                )
                                                ->orderBy('sort_order')
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->all();
                                        }
                                    ),

                                DatePicker::make('valid_from')
                                    ->label('Gültig ab')
                                    ->required()
                                    ->native(false)
                                    ->default(now()),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel(
                                'Funktion hinzufügen'
                            ),
                    ]),
            ])
            ->action(
                static function (
                    array $data,
                    RegisterCompleteMember $register,
                ): void {
                    $memberId =
                        (string) Str::uuid();

                    $register->handle(
                        memberId: $memberId,

                        memberNumber: trim(
                            $data['member_number']
                        ),

                        firstName: trim(
                            $data['first_name']
                        ),

                        lastName: trim(
                            $data['last_name']
                        ),

                        birthDate: filled(
                            $data['birth_date']
                            ?? null
                        )
                            ? CarbonImmutable::parse(
                                $data['birth_date']
                            )
                            : null,

                        joinedAt: CarbonImmutable::parse(
                            $data['joined_at']
                        ),

                        membershipTypeId: filled(
                            $data[
                            'membership_type_id'
                            ] ?? null
                        )
                            ? (string) $data[
                        'membership_type_id'
                        ]
                            : null,

                        street: self::nullableString(
                            $data['street']
                            ?? null
                        ),

                        houseNumber: self::nullableString(
                            $data[
                            'house_number'
                            ] ?? null
                        ),

                        postalCode: self::nullableString(
                            $data[
                            'postal_code'
                            ] ?? null
                        ),

                        city: self::nullableString(
                            $data['city']
                            ?? null
                        ),

                        countryCode: self::nullableString(
                            $data[
                            'country_code'
                            ] ?? null
                        ),

                        email: self::nullableString(
                            $data['email']
                            ?? null
                        ),

                        phone: self::nullableString(
                            $data['phone']
                            ?? null
                        ),

                        mobile: self::nullableString(
                            $data['mobile']
                            ?? null
                        ),

                        departmentIds: array_map(
                            static fn (
                                mixed $id
                            ): string => (string) $id,

                            $data[
                            'department_ids'
                            ] ?? [],
                        ),

                        functions: $data['functions']
                        ?? [],
                    );

                    Notification::make()
                        ->title(
                            'Mitglied vollständig angelegt'
                        )
                        ->success()
                        ->send();
                }
            );
    }

    private static function nullableString(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
