<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Domain\Membership\Enums\MembershipStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MemberInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Abteilungen')
                    ->schema([
                        RepeatableEntry::make('departments')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Abteilung'),
                            ])
                            ->columns(1),
                    ]),
                Section::make('Vereinsfunktionen')
                    ->schema([
                        RepeatableEntry::make(
                            'activeFunctionAssignments'
                        )
                            ->label('')
                            ->schema([
                                TextEntry::make(
                                    'clubFunction.name'
                                )
                                    ->label('Funktion'),

                                TextEntry::make('valid_from')
                                    ->label('Seit')
                                    ->date('d.m.Y'),
                            ])
                            ->columns(2),
                    ]),
                Section::make('Funktionshistorie')
                    ->collapsed()
                    ->schema([
                        RepeatableEntry::make(
                            'functionAssignments'
                        )
                            ->label('')
                            ->schema([
                                TextEntry::make(
                                    'clubFunction.name'
                                )
                                    ->label('Funktion'),

                                TextEntry::make('valid_from')
                                    ->label('Von')
                                    ->date('d.m.Y'),

                                TextEntry::make('valid_until')
                                    ->label('Bis')
                                    ->date('d.m.Y')
                                    ->placeholder('heute'),
                            ])
                            ->columns(3),
                    ]),
                TextEntry::make('uuid')
                    ->label('UUID'),
                TextEntry::make('club.name')
                    ->label('Club'),
                TextEntry::make('member_number'),
                TextEntry::make('first_name'),
                TextEntry::make('last_name'),
                TextEntry::make('birth_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        static fn (
                            MembershipStatus $state,
                        ): string => match ($state) {
                            MembershipStatus::Active => 'Aktiv',

                            MembershipStatus::Suspended => 'Gesperrt',

                            MembershipStatus::Left => 'Ausgetreten',
                        }
                    )
                    ->color(
                        static fn (
                            MembershipStatus $state,
                        ): string => match ($state) {
                            MembershipStatus::Active => 'success',

                            MembershipStatus::Suspended => 'warning',

                            MembershipStatus::Left => 'danger',
                        }
                    ),
                TextEntry::make('joined_at')
                    ->label('Eintritt')
                    ->date('d.m.Y'),
                TextEntry::make('left_at')
                    ->label('Austritt')
                    ->date('d.m.Y')
                    ->placeholder('–'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('street')
                    ->placeholder('-'),
                TextEntry::make('house_number')
                    ->placeholder('-'),
                TextEntry::make('postal_code')
                    ->placeholder('-'),
                TextEntry::make('city')
                    ->placeholder('-'),
                TextEntry::make('country_code')
                    ->placeholder('-'),
                TextEntry::make('mobile')
                    ->placeholder('-'),
                TextEntry::make(
                    'membershipType.name'
                )
                    ->label('Mitgliedsart')
                    ->placeholder('Keine Mitgliedsart'),
            ]);
    }
}
