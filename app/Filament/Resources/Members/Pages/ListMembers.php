<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\Actions\RegisterMemberAction;
use App\Filament\Resources\Members\MemberResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RegisterMemberAction::make(),
        ];
    }

    protected Width|string|null $maxContentWidth = Width::Full;
}
