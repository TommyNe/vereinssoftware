<?php

namespace App\Filament\Resources\ClubInvitations\Pages;

use App\Filament\Resources\ClubInvitations\ClubInvitationResource;
use Filament\Resources\Pages\ManageRecords;

class ManageClubInvitations extends ManageRecords
{
    protected static string $resource = ClubInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
