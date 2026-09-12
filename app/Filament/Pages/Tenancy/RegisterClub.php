<?php

namespace App\Filament\Pages\Tenancy;

use App\Domain\Club\Models\Club;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class RegisterClub extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Verein anlegen';
    }

    public function form(
        Schema $schema,
    ): Schema {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Vereinsname')
                    ->required()
                    ->maxLength(255),

                TextInput::make('short_name')
                    ->label('Kurzname')
                    ->maxLength(100),

                TextInput::make('email')
                    ->label('E-Mail-Adresse')
                    ->email()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Telefon')
                    ->maxLength(50),

                TextInput::make('street')
                    ->label('Straße')
                    ->maxLength(255),

                TextInput::make('postal_code')
                    ->label('PLZ')
                    ->maxLength(20),

                TextInput::make('city')
                    ->label('Ort')
                    ->maxLength(255),
            ]);
    }

    /**
     * @throws Throwable
     */
    protected function handleRegistration(
        array $data,
    ): Model {
        return DB::transaction(
            function () use ($data): Club {
                $user = auth()->user();

                if (! $user instanceof User) {
                    throw new RuntimeException(
                        'Kein angemeldeter Benutzer vorhanden.'
                    );
                }

                $club = Club::query()
                    ->create($data);

                $user
                    ->clubs()
                    ->attach(
                        $club->getKey()
                    );

                setPermissionsTeamId(
                    $club->getKey()
                );

                $user->unsetRelation('roles');
                $user->unsetRelation('permissions');

                $user->assignRole(
                    Role::Administrator->value
                );

                return $club;
            }
        );
    }
}
