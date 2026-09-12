<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Domain\Membership\Enums\MembershipStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('club_id')
                    ->relationship('club', 'name')
                    ->required(),
                TextInput::make('member_number')
                    ->required(),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                DatePicker::make('birth_date'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                Select::make('status')
                    ->options(MembershipStatus::class)
                    ->default('active')
                    ->required(),
                DatePicker::make('joined_at')
                    ->required(),
                DatePicker::make('left_at'),
                TextInput::make('street'),
                TextInput::make('house_number'),
                TextInput::make('postal_code'),
                TextInput::make('city'),
                TextInput::make('country_code')
                    ->default('DE'),
                TextInput::make('mobile'),
                Select::make('membership_type_id')
                    ->relationship('membershipType', 'name'),
            ]);
    }
}
