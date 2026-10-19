<?php

namespace App\Filament\Resources\ContributionCharges\Schemas;

use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContributionChargeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('member.member_number')
                    ->label('Mitgliedsnummer'),

                TextEntry::make('member.last_name')
                    ->label('Mitglied')
                    ->formatStateUsing(static fn (ContributionCharge $record): string => trim($record->member->first_name.' '.$record->member->last_name)),

                TextEntry::make('description')
                    ->label('Forderung'),

                TextEntry::make('contributionType.name')
                    ->label('Beitragsart'),

                TextEntry::make('amount')
                    ->label('Betrag')
                    ->money('EUR'),

                TextEntry::make('status')
                    ->label('Status')
                    ->formatStateUsing(static fn (ContributionChargeStatus $state): string => $state->label())
                    ->badge(),

                TextEntry::make('due_date')
                    ->label('Fällig am')
                    ->date('d.m.Y'),

                TextEntry::make('period_from')
                    ->label('Zeitraum von')
                    ->date('d.m.Y'),

                TextEntry::make('period_until')
                    ->label('Zeitraum bis')
                    ->date('d.m.Y')
                    ->placeholder('—'),
            ]);
    }
}
