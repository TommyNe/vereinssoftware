<?php

namespace App\Filament\Resources\ClubFunctions\Pages;

use App\Filament\Resources\ClubFunctions\ClubFunctionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageClubFunctions extends ManageRecords
{
    protected static string $resource = ClubFunctionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Vereinsfunktion erstellen'),
        ];
    }
}
