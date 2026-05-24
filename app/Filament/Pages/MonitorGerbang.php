<?php

namespace App\Filament\Pages;

use App\Models\RfidScanLog;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class MonitorGerbang extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static \UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Monitor Gerbang';

    protected static ?string $navigationLabel = 'Monitor Gerbang';

    protected string $view = 'filament.pages.monitor-gerbang';

    /**
     * @return Collection<int, RfidScanLog>
     */
    public function getRecentScans(): Collection
    {
        return RfidScanLog::query()
            ->with(['owner', 'device'])
            ->whereDate('scanned_at', today())
            ->orderByDesc('scanned_at')
            ->limit(20)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function getCounters(): array
    {
        $today = today();

        return [
            'masuk' => RfidScanLog::whereDate('scanned_at', $today)->where('jenis', 'masuk')->count(),
            'pulang' => RfidScanLog::whereDate('scanned_at', $today)->where('jenis', 'pulang')->count(),
            'ditolak' => RfidScanLog::whereDate('scanned_at', $today)->where('jenis', 'ditolak')->count(),
            'tidak_dikenal' => RfidScanLog::whereDate('scanned_at', $today)->where('jenis', 'tidak_dikenal')->count(),
        ];
    }
}
