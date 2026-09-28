<?php

namespace App\Filament\Resources\ContributionRates\Pages;

use App\Filament\Resources\ContributionRates\ContributionRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContributionRates extends ListRecords
{
    protected static string $resource = ContributionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
