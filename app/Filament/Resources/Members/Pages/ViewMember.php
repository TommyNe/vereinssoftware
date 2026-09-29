<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\Actions\AddContributionOverrideAction;
use App\Filament\Resources\Members\Actions\CreateContributionChargeAction;
use App\Filament\Resources\Members\MemberResource;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                AddContributionOverrideAction::make(),
                CreateContributionChargeAction::make(),
            ])
                ->label('Beiträge')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
