<?php

namespace App\Filament\Resources\ContributionRuns\Pages;

use App\Filament\Resources\ContributionRuns\ContributionRunResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContributionRun extends EditRecord
{
    protected static string $resource = ContributionRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
