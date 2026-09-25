<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\Actions\AssignFunctionAction;
use App\Filament\Resources\Members\Actions\ChangeAddressAction;
use App\Filament\Resources\Members\Actions\ChangeContactDataAction;
use App\Filament\Resources\Members\Actions\ChangeMembershipTypeAction;
use App\Filament\Resources\Members\Actions\UploadDocumentAction;
use App\Filament\Resources\Members\Actions\ChangePersonalDataAction;
use App\Filament\Resources\Members\Actions\EndFunctionAction;
use App\Filament\Resources\Members\Actions\JoinDepartmentAction;
use App\Filament\Resources\Members\Actions\LeaveDepartmentAction;
use App\Filament\Resources\Members\Actions\LeaveMemberAction;
use App\Filament\Resources\Members\Actions\ReactivateMemberAction;
use App\Filament\Resources\Members\Actions\SuspendMemberAction;
use App\Filament\Resources\Members\MemberResource;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ChangePersonalDataAction::make(),
                ChangeAddressAction::make(),
                ChangeContactDataAction::make(),
            ])
                ->label('Daten')
                ->icon('heroicon-o-pencil-square'),

            ActionGroup::make([
                ChangeMembershipTypeAction::make(),
                SuspendMemberAction::make(),
                ReactivateMemberAction::make(),
                LeaveMemberAction::make(),
            ])
                ->label('Mitgliedschaft')
                ->icon('heroicon-o-user-circle'),

            ActionGroup::make([
                JoinDepartmentAction::make(),
                LeaveDepartmentAction::make(),
            ])
                ->label('Abteilungen')
                ->icon('heroicon-o-building-office'),

            ActionGroup::make([
                AssignFunctionAction::make(),
                EndFunctionAction::make(),
            ])
                ->label('Funktionen')
                ->icon('heroicon-o-briefcase'),

            ActionGroup::make([
                UploadDocumentAction::make(),
            ])
                ->label('Dokumente')
                ->icon(
                    'heroicon-o-document-text'
                ),
        ];
    }
}
