<?php

namespace App\Filament\Resources\Members\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class MemberInfolist
{
    public static function configure(
        Schema $schema,
    ): Schema {
        return $schema
            ->components([
                Section::make('Persönliche Daten')
                    ->schema([
                        TextEntry::make('member_number')
                            ->label('Mitgliedsnummer'),

                        TextEntry::make('first_name')
                            ->label('Vorname'),

                        TextEntry::make('last_name')
                            ->label('Nachname'),

                        TextEntry::make('birth_date')
                            ->label('Geburtsdatum')
                            ->date('d.m.Y'),
                    ])
                    ->columns(2),

                Section::make('Adresse')
                    ->schema([
                        TextEntry::make('street')
                            ->label('Straße'),

                        TextEntry::make('house_number')
                            ->label('Hausnummer'),

                        TextEntry::make('postal_code')
                            ->label('PLZ'),

                        TextEntry::make('city')
                            ->label('Ort'),

                        TextEntry::make('country_code')
                            ->label('Land'),
                    ])
                    ->columns(2),

                Section::make('Kontakt')
                    ->schema([
                        TextEntry::make('email')
                            ->label('E-Mail'),

                        TextEntry::make('phone')
                            ->label('Telefon'),

                        TextEntry::make('mobile')
                            ->label('Mobil'),
                    ])
                    ->columns(2),

                Section::make('Mitgliedschaft')
                    ->schema([
                        TextEntry::make('membershipType.name')
                            ->label('Mitgliedsart')
                            ->placeholder('–'),

                        TextEntry::make('status')
                            ->label('Status'),

                        TextEntry::make('joined_at')
                            ->label('Eintritt')
                            ->date('d.m.Y'),

                        TextEntry::make('left_at')
                            ->label('Austritt')
                            ->date('d.m.Y')
                            ->placeholder('–'),
                    ])
                    ->columns(2),
            ]);
    }
}
