<?php

namespace App\Filament\Resources\ContributionTypes\Pages;

use App\Filament\Resources\ContributionTypes\ContributionTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContributionType extends EditRecord
{
    protected static string $resource = ContributionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
