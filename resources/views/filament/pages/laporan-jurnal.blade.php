<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Data
        </x-slot>
        <x-slot name="description">
            Pilih rentang tanggal untuk melihat jurnal umum
        </x-slot>

        {{ $this->filtersForm }}
    </x-filament::section>

    {{-- Data Table --}}
    <x-filament::section icon="heroicon-o-book-open" icon-color="info">
        <x-slot name="heading">
            Data Jurnal Umum
        </x-slot>

        <div class="fi-ta-content relative divide-y divide-gray-200 dark:divide-white/10" style="overflow-x: auto">
            <table class="fi-ta-table w-full min-w-[700px] table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white sm:px-4">#</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white sm:px-4 whitespace-nowrap">Tanggal</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white sm:px-4 whitespace-nowrap">No. Bukti</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white sm:px-4">Akun</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white sm:px-4">Keterangan</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white sm:px-4 whitespace-nowrap">Debit</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white sm:px-4 whitespace-nowrap">Kredit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-3 py-3 text-sm text-gray-500 dark:text-gray-400 sm:px-4">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-sm text-gray-950 dark:text-white sm:px-4 whitespace-nowrap">{{ $item['tanggal'] }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-sm font-mono text-gray-950 dark:text-white sm:px-4 whitespace-nowrap">{{ $item['nomor_bukti'] }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-sm font-medium text-gray-950 dark:text-white sm:px-4">{{ $item['akun'] }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-sm text-gray-950 dark:text-white sm:px-4">{{ $item['keterangan'] }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-end text-sm font-medium text-success-600 dark:text-success-400 sm:px-4 whitespace-nowrap">
                                {{ $item['debit'] > 0 ? 'Rp ' . number_format($item['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="fi-ta-cell px-3 py-3 text-end text-sm font-medium text-danger-600 dark:text-danger-400 sm:px-4 whitespace-nowrap">
                                {{ $item['kredit'] > 0 ? 'Rp ' . number_format($item['kredit'], 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center">
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
                            <td colspan="5" class="px-3 py-3 text-sm font-bold text-gray-950 dark:text-white sm:px-4">Total</td>
                            <td class="px-3 py-3 text-end text-sm font-bold text-success-600 dark:text-success-400 sm:px-4 whitespace-nowrap">
                                Rp {{ number_format($summary['total_debit'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-end text-sm font-bold text-danger-600 dark:text-danger-400 sm:px-4 whitespace-nowrap">
                                Rp {{ number_format($summary['total_kredit'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
