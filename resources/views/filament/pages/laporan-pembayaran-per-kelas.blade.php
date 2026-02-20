<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Data
        </x-slot>
        <x-slot name="description">
            Pilih semester dan kelas untuk melihat data pembayaran
        </x-slot>

        {{ $this->filtersForm }}
    </x-filament::section>

    {{-- Data Table --}}
    <x-filament::section icon="heroicon-o-table-cells" icon-color="info">
        <x-slot name="heading">
            Data Pembayaran Siswa
        </x-slot>

        <div class="fi-ta-content relative divide-y divide-gray-200 dark:divide-white/10" style="overflow-x: auto">
            <table class="fi-ta-table min-w-[700px] w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">NIS</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Nama Siswa</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Total Tagihan</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Terbayar</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Sisa</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-center text-sm font-semibold text-gray-950 dark:text-white">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-sm font-mono whitespace-nowrap text-gray-950 dark:text-white">{{ $item['nis'] }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $item['nama'] }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-end text-sm whitespace-nowrap text-gray-950 dark:text-white">Rp {{ number_format($item['total_tagihan'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-end text-sm font-medium whitespace-nowrap text-success-600 dark:text-success-400">Rp {{ number_format($item['total_terbayar'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-end text-sm font-medium whitespace-nowrap text-danger-600 dark:text-danger-400">Rp {{ number_format($item['sisa'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-center text-sm whitespace-nowrap">
                                @if($item['status'] === 'Lunas')
                                    <x-filament::badge color="success">Lunas</x-filament::badge>
                                @else
                                    <x-filament::badge color="danger">Belum Lunas</x-filament::badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 sm:px-4 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-inbox" class="mb-2 h-12 w-12" />
                                    <p class="text-sm">Tidak ada data. Silakan pilih semester dan kelas.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
