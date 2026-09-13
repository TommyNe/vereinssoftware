<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\Actions\ChangeAddressAction;
use App\Filament\Resources\Members\Actions\ChangeContactDataAction;
use App\Filament\Resources\Members\Actions\ChangePersonalDataAction;
use App\Filament\Resources\Members\MemberResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChangePersonalDataAction::make(),
            ChangeAddressAction::make(),
            ChangeContactDataAction::make(),
        ];
    }
}
