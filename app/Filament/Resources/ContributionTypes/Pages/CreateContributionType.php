<?php

namespace App\Filament\Resources\ContributionTypes\Pages;

use App\Filament\Resources\ContributionTypes\ContributionTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContributionType extends CreateRecord
{
    protected static string $resource = ContributionTypeResource::class;
}
