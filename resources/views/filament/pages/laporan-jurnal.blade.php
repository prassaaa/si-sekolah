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

        <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Tanggal</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">No. Bukti</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Akun</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Keterangan</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Debit</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Kredit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-sm text-gray-950 dark:text-white">{{ $item['tanggal'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-sm font-mono text-xs text-gray-950 dark:text-white">{{ $item['nomor_bukti'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $item['akun'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-sm text-gray-950 dark:text-white">{{ $item['keterangan'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm font-medium text-success-600 dark:text-success-400">
                                {{ $item['debit'] > 0 ? 'Rp ' . number_format($item['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm font-medium text-danger-600 dark:text-danger-400">
                                {{ $item['kredit'] > 0 ? 'Rp ' . number_format($item['kredit'], 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-inbox" class="h-12 w-12 mb-2" />
                                    <p class="text-sm">Tidak ada data. Silakan pilih rentang tanggal.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data->count() > 0)
                    <tfoot class="bg-gray-100 dark:bg-white/10">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-sm font-bold text-gray-950 dark:text-white">Total</td>
                            <td class="px-4 py-3 text-end text-sm font-bold text-success-600 dark:text-success-400">
                                Rp {{ number_format($summary['total_debit'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-end text-sm font-bold text-danger-600 dark:text-danger-400">
                                Rp {{ number_format($summary['total_kredit'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
