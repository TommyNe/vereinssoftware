<?php

namespace App\Filament\Resources\Members\Tables;

use App\Domain\Membership\Enums\MembershipStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class MembersTable
{
    public static function configure(
        Table $table,
    ): Table {
        return $table
            ->columns([
                TextColumn::make('member_number')
                    ->label('Mitgliedsnummer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_name')
                    ->label('Nachname')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('first_name')
                    ->label('Vorname')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('birth_date')
                    ->label('Geburtsdatum')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('membershipType.name')
                    ->label('Mitgliedsart')
                    ->placeholder('–'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (MembershipStatus $state): string =>
                        match ($state) {
                            MembershipStatus::Active =>
                            'Aktiv',

                            MembershipStatus::Suspended =>
                            'Gesperrt',

                            MembershipStatus::Left =>
                            'Ausgetreten',
                        }
                    ),

                TextColumn::make('joined_at')
                    ->label('Eintritt')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
