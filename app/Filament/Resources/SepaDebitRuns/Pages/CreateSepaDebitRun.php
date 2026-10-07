<?php

namespace App\Filament\Resources\SepaDebitRuns\Pages;

use App\Filament\Resources\SepaDebitRuns\SepaDebitRunResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSepaDebitRun extends CreateRecord
{
    protected static string $resource = SepaDebitRunResource::class;
}
