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

    {{-- Stats Cards --}}
    @if($summary)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/10">
                        <x-heroicon-o-users class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Siswa</p>
                        <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $summary['total_siswa'] ?? 0 }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 dark:bg-success-500/10">
                        <x-heroicon-o-book-open class="h-6 w-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Setoran</p>
                        <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $summary['total_setoran'] ?? 0 }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-50 dark:bg-info-500/10">
                        <x-heroicon-o-arrow-path class="h-6 w-6 text-info-600 dark:text-info-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Muroja'ah</p>
                        <p class="text-2xl font-bold text-info-600 dark:text-info-400">{{ $summary['total_murojaah'] ?? 0 }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning-50 dark:bg-warning-500/10">
                        <x-heroicon-o-document-text class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Ayat</p>
                        <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $summary['total_ayat'] ?? 0 }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-500/10">
                        <x-heroicon-o-academic-cap class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rata-rata Nilai</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['rata_rata_nilai'] ?? 0 }}</p>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif

    {{-- Data Table --}}
    <x-filament::section icon="heroicon-o-book-open" icon-color="success">
        <x-slot name="heading">
            Data Rekap Tahfidz
        </x-slot>

        <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Nama Siswa</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Kelas</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Setoran</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Muroja'ah</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Total Ayat</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Rata-rata</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Lulus</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Belum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $item['siswa'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-sm text-gray-950 dark:text-white">{{ $item['kelas'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm text-gray-950 dark:text-white">{{ $item['total_setoran'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm text-gray-950 dark:text-white">{{ $item["total_muroja'ah"] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm text-gray-950 dark:text-white">{{ $item['total_ayat'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm font-medium text-gray-950 dark:text-white">{{ $item['rata_rata_nilai'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm">
                                <x-filament::badge color="success">{{ $item['lulus'] }}</x-filament::badge>
                            </td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm">
                                <x-filament::badge color="danger">{{ $item['belum_lulus'] }}</x-filament::badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-inbox class="h-12 w-12 mb-2" />
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
