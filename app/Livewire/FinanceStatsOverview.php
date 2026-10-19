<?php

namespace App\Livewire;

use App\Application\Contribution\FinanceOverviewService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class FinanceStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $overview =
            app(
                FinanceOverviewService::class
            )->getOverview();

        return [
            Stat::make(
                'Offene Forderungen',
                number_format(
                    (float) $overview->totalOpen,
                    2,
                    ',',
                    '.'
                ).' €'
            )
                ->description(
                    $overview->openChargesCount
                    .' offene Forderungen'
                )
                ->icon(
                    'heroicon-o-receipt-percent'
                ),

            Stat::make(
                'Überfällig',
                number_format(
                    (float) $overview->totalOverdue,
                    2,
                    ',',
                    '.'
                ).' €'
            )
                ->description(
                    $overview->overdueChargesCount
                    .' überfällige Forderungen'
                )
                ->icon(
                    'heroicon-o-exclamation-triangle'
                ),

            Stat::make(
                'Teilweise bezahlt',
                number_format(
                    (float) $overview->totalPartiallyPaid,
                    2,
                    ',',
                    '.'
                ).' €'
            )
                ->description(
                    $overview
                        ->partiallyPaidChargesCount
                    .' teilweise bezahlt'
                )
                ->icon(
                    'heroicon-o-adjustments-horizontal'
                ),

            Stat::make(
                'Nicht zugeordnet',
                number_format(
                    (float) $overview
                        ->totalUnallocatedPayments,
                    2,
                    ',',
                    '.'
                ).' €'
            )
                ->description(
                    $overview
                        ->unallocatedPaymentsCount
                    .' Zahlungen'
                )
                ->icon(
                    'heroicon-o-question-mark-circle'
                ),
        ];
    }
}
