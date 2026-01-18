<?php

namespace App\Filament\Widgets;

use App\Models\Siswa;
use Filament\Widgets\ChartWidget;

class SiswaChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Status Siswa';

    protected static ?int $sort = 6;

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $statusCounts = Siswa::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $labels = [
            'aktif' => 'Aktif',
            'alumni' => 'Alumni',
            'pindah' => 'Pindah',
            'dikeluarkan' => 'Dikeluarkan',
            'mengundurkan_diri' => 'Mengundurkan Diri',
        ];

        $data = [];
        $chartLabels = [];
        $colors = [
            'aktif' => 'rgba(34, 197, 94, 0.8)',
            'alumni' => 'rgba(59, 130, 246, 0.8)',
            'pindah' => 'rgba(249, 115, 22, 0.8)',
            'dikeluarkan' => 'rgba(239, 68, 68, 0.8)',
            'mengundurkan_diri' => 'rgba(107, 114, 128, 0.8)',
        ];

        foreach ($labels as $key => $label) {
            $chartLabels[] = $label;
            $data[] = $statusCounts[$key] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Siswa',
                    'data' => $data,
                    'backgroundColor' => array_values($colors),
                ],
            ],
            'labels' => $chartLabels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
