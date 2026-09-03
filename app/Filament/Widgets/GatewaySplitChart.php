<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class GatewaySplitChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue by gateway';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return false;
    }

    protected function getData(): array
    {
        $successful = Payment::where('status', Payment::STATUS_SUCCESSFUL);

        $paystackKobo = (clone $successful)->where('gateway', 'paystack')->sum('amount_kobo');
        $flutterwaveKobo = (clone $successful)->where('gateway', 'flutterwave')->sum('amount_kobo');

        return [
            'datasets' => [
                [
                    'data' => [$paystackKobo / 100, $flutterwaveKobo / 100],
                    'backgroundColor' => ['#15130F', '#F8E5B6'],
                    'borderColor' => ['#15130F', '#E7E4DE'],
                ],
            ],
            'labels' => ['Paystack', 'Flutterwave'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}
