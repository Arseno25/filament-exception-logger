<?php

namespace Arseno25\ExceptionLogger\Widgets;

use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class ExceptionStatsOverview extends ChartWidget
{
    protected ?string $heading = 'Errors (Last 7 Days)';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = Trend::model(ExceptionLog::class)
            ->between(
                start: now()->subDays(7),
                end: now(),
            )
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Exceptions',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#ef4444', // Merah
                    'backgroundColor' => '#fee2e2',
                    'fill' => true,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
