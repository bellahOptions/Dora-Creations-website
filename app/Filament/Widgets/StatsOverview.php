<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $paidOrders = Order::whereNotNull('paid_at');

        $revenueKobo = (clone $paidOrders)->sum('total_kobo');
        $ordersCount = (clone $paidOrders)->count();
        $pendingCount = Order::where('status', Order::STATUS_PROCESSING)->count();
        $newCustomers = User::where('is_admin', false)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            Stat::make('Revenue', '₦'.number_format($revenueKobo / 100, 2))
                ->description('All-time paid orders')
                ->color('success'),
            Stat::make('Orders', (string) $ordersCount)
                ->description('All-time paid orders')
                ->color('primary'),
            Stat::make('Processing', (string) $pendingCount)
                ->description('Orders awaiting fulfilment')
                ->color('warning'),
            Stat::make('New customers', (string) $newCustomers)
                ->description('Last 30 days')
                ->color('gray'),
        ];
    }
}
