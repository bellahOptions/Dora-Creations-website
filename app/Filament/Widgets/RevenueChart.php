<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue (last 30 days)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo)->startOfDay());

        $revenueByDay = Order::query()
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subDays(30)->startOfDay())
            ->get()
            ->groupBy(fn (Order $order) => $order->paid_at->format('Y-m-d'));

        $data = $days->map(fn ($day) => round(($revenueByDay->get($day->format('Y-m-d'))?->sum('total_kobo') ?? 0) / 100, 2));

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (₦)',
                    'data' => $data->values(),
                    'borderColor' => '#CC5E1E',
                    'backgroundColor' => 'rgba(204, 94, 30, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn ($day) => $day->format('d M'))->values(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
