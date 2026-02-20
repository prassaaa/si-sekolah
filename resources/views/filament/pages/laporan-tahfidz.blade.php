<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Data
        </x-slot>
        <x-slot name="description">
            Pilih semester untuk melihat rekap tahfidz siswa
        </x-slot>

        {{ $this->filtersForm }}
    </x-filament::section>

    {{-- Data Table --}}
    <x-filament::section icon="heroicon-o-book-open" icon-color="success">
        <x-slot name="heading">
            Data Rekap Tahfidz
        </x-slot>

        <div class="fi-ta-content relative divide-y divide-gray-200 dark:divide-white/10" style="overflow-x: auto">
            <table class="fi-ta-table min-w-[750px] w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-2 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                        <th class="fi-ta-header-cell px-2 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Nama Siswa</th>
                        <th class="fi-ta-header-cell px-2 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Kelas</th>
                        <th class="fi-ta-header-cell px-2 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Setoran</th>
                        <th class="fi-ta-header-cell px-2 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Muroja'ah</th>
                        <th class="fi-ta-header-cell px-2 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Total Ayat</th>
                        <th class="fi-ta-header-cell px-2 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Rata-rata</th>
                        <th class="fi-ta-header-cell px-2 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Lulus</th>
                        <th class="fi-ta-header-cell px-2 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Belum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-2 sm:px-4 py-3 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell px-2 sm:px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $item['siswa'] }}</td>
                            <td class="fi-ta-cell px-2 sm:px-4 py-3 text-sm text-gray-950 dark:text-white">{{ $item['kelas'] }}</td>
                            <td class="fi-ta-cell px-2 sm:px-4 py-3 text-end text-sm whitespace-nowrap text-gray-950 dark:text-white">{{ $item['total_setoran'] }}</td>
                            <td class="fi-ta-cell px-2 sm:px-4 py-3 text-end text-sm whitespace-nowrap text-gray-950 dark:text-white">{{ $item["total_muroja'ah"] }}</td>
                            <td class="fi-ta-cell px-2 sm:px-4 py-3 text-end text-sm whitespace-nowrap text-gray-950 dark:text-white">{{ $item['total_ayat'] }}</td>
                            <td class="fi-ta-cell px-2 sm:px-4 py-3 text-end text-sm font-medium whitespace-nowrap text-gray-950 dark:text-white">{{ $item['rata_rata_nilai'] }}</td>
                            <td class="fi-ta-cell px-2 sm:px-4 py-3 text-end text-sm whitespace-nowrap">
                                <x-filament::badge color="success">{{ $item['lulus'] }}</x-filament::badge>
                            </td>
                            <td class="fi-ta-cell px-2 sm:px-4 py-3 text-end text-sm whitespace-nowrap">
                                <x-filament::badge color="danger">{{ $item['belum_lulus'] }}</x-filament::badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-2 sm:px-4 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-inbox" class="mb-2 h-12 w-12" />
                                    <p class="text-sm">Tidak ada data. Silakan pilih semester.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
