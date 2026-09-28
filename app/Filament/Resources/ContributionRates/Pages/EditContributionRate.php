<?php

namespace App\Filament\Resources\ContributionRates\Pages;

use App\Filament\Resources\ContributionRates\ContributionRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContributionRate extends EditRecord
{
    protected static string $resource = ContributionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
