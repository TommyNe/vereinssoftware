<?php

namespace App\Filament\Pages;

use App\Application\Contribution\Data\FinanceOverviewData;
use App\Application\Contribution\FinanceOverviewService;
use App\Domain\Identity\Enums\Permission;
use App\Livewire\FinanceStatsOverview;
use App\Livewire\OpenContributionChargesTable;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

final class FinanceOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup =
        'Finanzen';

    protected static ?string $navigationLabel =
        'Übersicht';

    protected static ?string $title =
        'Finanzübersicht';

    protected static ?int $navigationSort =
        0;

    protected string $view =
        'filament.pages.finance-overview';

    public static function canAccess(): bool
    {
        $user =
            Auth::user();

        return $user !== null
            && $user->can(
                Permission::FinanceOverviewView
                    ->value
            );
    }

    public function getOverview(): FinanceOverviewData
    {
        return app(
            FinanceOverviewService::class
        )->getOverview();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            OpenContributionChargesTable::class,
        ];
    }
}
