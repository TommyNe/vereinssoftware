<?php

namespace App\Filament\Resources\SepaDebitRuns\Pages;

use App\Filament\Resources\SepaDebitRuns\SepaDebitRunResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSepaDebitRun extends EditRecord
{
    protected static string $resource = SepaDebitRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
