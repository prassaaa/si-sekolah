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

    {{-- Data Table --}}
    <x-filament::section icon="heroicon-o-calendar-days" icon-color="info">
        <x-slot name="heading">
            Rekap Pembayaran Per Tanggal
        </x-slot>

        <div class="fi-ta-content relative divide-y divide-gray-200 dark:divide-white/10" style="overflow-x: auto">
            <table class="fi-ta-table w-full min-w-[700px] table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 sm:px-4 dark:text-white">#</th>
                        <th class="fi-ta-header-cell whitespace-nowrap px-3 py-3 text-start text-sm font-semibold text-gray-950 sm:px-4 dark:text-white">Tanggal</th>
                        <th class="fi-ta-header-cell whitespace-nowrap px-3 py-3 text-end text-sm font-semibold text-gray-950 sm:px-4 dark:text-white">Transaksi</th>
                        <th class="fi-ta-header-cell whitespace-nowrap px-3 py-3 text-end text-sm font-semibold text-gray-950 sm:px-4 dark:text-white">Tunai</th>
                        <th class="fi-ta-header-cell whitespace-nowrap px-3 py-3 text-end text-sm font-semibold text-gray-950 sm:px-4 dark:text-white">Transfer</th>
                        <th class="fi-ta-header-cell whitespace-nowrap px-3 py-3 text-end text-sm font-semibold text-gray-950 sm:px-4 dark:text-white">Lainnya</th>
                        <th class="fi-ta-header-cell whitespace-nowrap px-3 py-3 text-end text-sm font-semibold text-gray-950 sm:px-4 dark:text-white">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-3 py-3 text-sm text-gray-500 sm:px-4 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell whitespace-nowrap px-3 py-3 text-sm font-medium text-gray-950 sm:px-4 dark:text-white">{{ \Carbon\Carbon::parse($item['tanggal'])->format('d M Y') }}</td>
                            <td class="fi-ta-cell whitespace-nowrap px-3 py-3 text-end text-sm text-gray-950 sm:px-4 dark:text-white">
                                <x-filament::badge color="gray">{{ $item['jumlah_transaksi'] }}</x-filament::badge>
                            </td>
                            <td class="fi-ta-cell whitespace-nowrap px-3 py-3 text-end text-sm text-success-600 sm:px-4 dark:text-success-400">Rp {{ number_format($item['tunai'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell whitespace-nowrap px-3 py-3 text-end text-sm text-info-600 sm:px-4 dark:text-info-400">Rp {{ number_format($item['transfer'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell whitespace-nowrap px-3 py-3 text-end text-sm text-warning-600 sm:px-4 dark:text-warning-400">Rp {{ number_format($item['lainnya'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell whitespace-nowrap px-3 py-3 text-end text-sm font-bold text-gray-950 sm:px-4 dark:text-white">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center sm:px-4">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-inbox" class="mb-2 h-12 w-12" />
                                    <p class="text-sm">Tidak ada data. Silakan pilih rentang tanggal.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data->count() > 0)
                    <tfoot class="bg-gray-100 dark:bg-white/10">
                        <tr>
                            <td colspan="2" class="px-3 py-3 text-sm font-bold text-gray-950 sm:px-4 dark:text-white">Total</td>
                            <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-bold text-gray-950 sm:px-4 dark:text-white">{{ $summary['total_transaksi'] ?? 0 }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-bold text-success-600 sm:px-4 dark:text-success-400">Rp {{ number_format($summary['total_tunai'] ?? 0, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-bold text-info-600 sm:px-4 dark:text-info-400">Rp {{ number_format($summary['total_transfer'] ?? 0, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-bold text-warning-600 sm:px-4 dark:text-warning-400">Rp {{ number_format($summary['total_lainnya'] ?? 0, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-end text-sm font-bold text-primary-600 sm:px-4 dark:text-primary-400">Rp {{ number_format($summary['grand_total'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
