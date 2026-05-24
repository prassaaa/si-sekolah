<x-filament-panels::page>
    <div wire:poll.5s="$refresh" class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @php($counters = $this->getCounters())

            <div class="rounded-xl bg-success-50 dark:bg-success-950 p-4 border border-success-200 dark:border-success-800">
                <div class="text-sm font-medium text-success-700 dark:text-success-300">Tap Masuk</div>
                <div class="mt-2 text-3xl font-bold text-success-900 dark:text-success-100">{{ $counters['masuk'] }}</div>
            </div>

            <div class="rounded-xl bg-info-50 dark:bg-info-950 p-4 border border-info-200 dark:border-info-800">
                <div class="text-sm font-medium text-info-700 dark:text-info-300">Tap Pulang</div>
                <div class="mt-2 text-3xl font-bold text-info-900 dark:text-info-100">{{ $counters['pulang'] }}</div>
            </div>

            <div class="rounded-xl bg-danger-50 dark:bg-danger-950 p-4 border border-danger-200 dark:border-danger-800">
                <div class="text-sm font-medium text-danger-700 dark:text-danger-300">Ditolak</div>
                <div class="mt-2 text-3xl font-bold text-danger-900 dark:text-danger-100">{{ $counters['ditolak'] }}</div>
            </div>

            <div class="rounded-xl bg-gray-50 dark:bg-gray-950 p-4 border border-gray-200 dark:border-gray-800">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Tidak Dikenal</div>
                <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $counters['tidak_dikenal'] }}</div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Scan Terakhir Hari Ini</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">Auto-refresh tiap 5 detik</span>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->getRecentScans() as $log)
                    <div wire:key="scan-{{ $log->id }}" class="flex items-center gap-4 px-4 py-3">
                        <div class="flex-shrink-0">
                            @php($color = $log->jenis_info['color'] ?? 'gray')
                            <span @class([
                                'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
                                'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200' => $color === 'success',
                                'bg-info-100 text-info-800 dark:bg-info-900 dark:text-info-200' => $color === 'info',
                                'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200' => $color === 'warning',
                                'bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200' => $color === 'danger',
                                'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' => $color === 'gray',
                            ])>
                                {{ $log->jenis_info['label'] ?? $log->jenis }}
                            </span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $log->owner?->nama ?? 'Tidak teridentifikasi' }}
                                @if ($log->owner_type)
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        ({{ $log->owner_type === \App\Models\Pegawai::class ? 'Pegawai' : 'Siswa' }})
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ $log->pesan }} · UID: <code class="font-mono">{{ $log->uid }}</code>
                            </div>
                        </div>

                        <div class="flex-shrink-0 text-right">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $log->scanned_at->format('H:i:s') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $log->device?->nama ?? '-' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        Belum ada scan hari ini.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
