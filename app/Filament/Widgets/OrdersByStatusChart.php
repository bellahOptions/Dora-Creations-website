<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

class OrdersByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Orders by status';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $statuses = [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PROCESSING,
            Order::STATUS_DELIVERY_ONGOING,
            Order::STATUS_DELIVERED,
            Order::STATUS_REJECTED_REFUNDED,
            Order::STATUS_REVIEW_REQUESTED,
        ];

        $counts = Order::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'datasets' => [
                [
                    'data' => collect($statuses)->map(fn ($status) => $counts->get($status, 0))->values(),
                    'backgroundColor' => ['#A29A89', '#E6934F', '#C9A227', '#1F6F54', '#DC2626', '#725C3A'],
                ],
            ],
            'labels' => collect($statuses)->map(fn ($status) => Str::headline($status))->values(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
