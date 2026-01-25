<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Data
        </x-slot>
        <x-slot name="description">
            Pilih rentang tanggal untuk melihat rekap pembayaran
        </x-slot>

        {{ $this->filtersForm }}
    </x-filament::section>

    {{-- Stats Cards --}}
    @if($summary)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-500/10">
                        <x-heroicon-o-document-text class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transaksi</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['total_transaksi'] ?? 0 }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 dark:bg-success-500/10">
                        <x-heroicon-o-banknotes class="h-6 w-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tunai</p>
                        <p class="text-lg font-bold text-success-600 dark:text-success-400">
                            Rp {{ number_format($summary['total_tunai'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-50 dark:bg-info-500/10">
                        <x-heroicon-o-building-library class="h-6 w-6 text-info-600 dark:text-info-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transfer</p>
                        <p class="text-lg font-bold text-info-600 dark:text-info-400">
                            Rp {{ number_format($summary['total_transfer'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning-50 dark:bg-warning-500/10">
                        <x-heroicon-o-credit-card class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Lainnya</p>
                        <p class="text-lg font-bold text-warning-600 dark:text-warning-400">
                            Rp {{ number_format($summary['total_lainnya'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/10">
                        <x-heroicon-o-wallet class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Grand Total</p>
                        <p class="text-lg font-bold text-primary-600 dark:text-primary-400">
                            Rp {{ number_format($summary['grand_total'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif

    {{-- Data Table --}}
    <x-filament::section icon="heroicon-o-calendar-days" icon-color="info">
        <x-slot name="heading">
            Rekap Pembayaran Per Tanggal
        </x-slot>

        <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Tanggal</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Transaksi</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Tunai</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Transfer</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Lainnya</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ \Carbon\Carbon::parse($item['tanggal'])->format('d M Y') }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm text-gray-950 dark:text-white">
                                <x-filament::badge color="gray">{{ $item['jumlah_transaksi'] }}</x-filament::badge>
                            </td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm text-success-600 dark:text-success-400">Rp {{ number_format($item['tunai'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm text-info-600 dark:text-info-400">Rp {{ number_format($item['transfer'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm text-warning-600 dark:text-warning-400">Rp {{ number_format($item['lainnya'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm font-bold text-gray-950 dark:text-white">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-inbox class="h-12 w-12 mb-2" />
                                    <p class="text-sm">Tidak ada data. Silakan pilih rentang tanggal.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data->count() > 0)
                    <tfoot class="bg-gray-100 dark:bg-white/10">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-sm font-bold text-gray-950 dark:text-white">Total</td>
                            <td class="px-4 py-3 text-end text-sm font-bold text-gray-950 dark:text-white">{{ $summary['total_transaksi'] ?? 0 }}</td>
                            <td class="px-4 py-3 text-end text-sm font-bold text-success-600 dark:text-success-400">Rp {{ number_format($summary['total_tunai'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-end text-sm font-bold text-info-600 dark:text-info-400">Rp {{ number_format($summary['total_transfer'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-end text-sm font-bold text-warning-600 dark:text-warning-400">Rp {{ number_format($summary['total_lainnya'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-end text-sm font-bold text-primary-600 dark:text-primary-400">Rp {{ number_format($summary['grand_total'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
