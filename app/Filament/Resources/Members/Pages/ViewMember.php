<?php

namespace App\Filament\Resources\Members\Pages;

use App\Application\Audit\AuditLogger;
use App\Domain\Audit\Enums\AuditAction;
use App\Filament\Resources\Members\Actions\AddContributionOverrideAction;
use App\Filament\Resources\Members\Actions\CancelContributionChargeAction;
use App\Filament\Resources\Members\Actions\CreateContributionChargeAction;
use App\Filament\Resources\Members\Actions\MarkContributionChargePaidAction;
use App\Filament\Resources\Members\MemberResource;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        app(AuditLogger::class)->log(AuditAction::MemberViewed, $this->getRecord(), properties: [
            'club_id' => $this->getRecord()->getAttribute('club_id'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                AddContributionOverrideAction::make(),
                CreateContributionChargeAction::make(),
                MarkContributionChargePaidAction::make(),
                CancelContributionChargeAction::make(),
            ])
                ->label('Beiträge')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
