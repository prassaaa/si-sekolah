<?php

namespace App\Filament\Widgets;

use App\Models\PresensiHarian;
use Filament\Widgets\ChartWidget;

class PresensiPerJamWidget extends ChartWidget
{
    protected ?string $heading = 'Tap Masuk Hari Ini (per Jam)';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $records = PresensiHarian::hariIni()
            ->whereNotNull('jam_masuk')
            ->get(['jam_masuk', 'status']);

        $buckets = [];
        for ($hour = 5; $hour <= 9; $hour++) {
            $buckets[sprintf('%02d:00', $hour)] = 0;
        }

        foreach ($records as $record) {
            $hour = (int) $record->jam_masuk->format('H');

            if ($hour < 5 || $hour > 9) {
                continue;
            }

            $key = sprintf('%02d:00', $hour);
            $buckets[$key]++;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Tap Masuk',
                    'data' => array_values($buckets),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
            ],
            'labels' => array_keys($buckets),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
