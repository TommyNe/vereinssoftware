<?php

namespace App\Filament\Resources\ContributionTypes\Pages;

use App\Filament\Resources\ContributionTypes\ContributionTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContributionTypes extends ListRecords
{
    protected static string $resource = ContributionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
