<?php

namespace App\Filament\Resources\SepaDebitRuns\Pages;

use App\Filament\Resources\SepaDebitRuns\SepaDebitRunResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSepaDebitRun extends ViewRecord
{
    protected static string $resource = SepaDebitRunResource::class;

    public function getTitle(): string
    {
        return (string) $this->getRecord()->getAttribute('name');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
