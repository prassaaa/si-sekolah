<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Data
        </x-slot>
        <x-slot name="description">
            Pilih bulan untuk melihat data slip gaji
        </x-slot>

        {{ $this->filtersForm }}
    </x-filament::section>

    {{-- Stats Cards --}}
    @if($summary)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-500/10">
                        <x-heroicon-o-users class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pegawai</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['total_pegawai'] ?? 0 }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-500/10">
                        <x-heroicon-o-banknotes class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gaji Pokok</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($summary['total_gaji_pokok'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 dark:bg-success-500/10">
                        <x-heroicon-o-plus-circle class="h-6 w-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tunjangan</p>
                        <p class="text-lg font-bold text-success-600 dark:text-success-400">
                            Rp {{ number_format($summary['total_tunjangan'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-50 dark:bg-danger-500/10">
                        <x-heroicon-o-minus-circle class="h-6 w-6 text-danger-600 dark:text-danger-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Potongan</p>
                        <p class="text-lg font-bold text-danger-600 dark:text-danger-400">
                            Rp {{ number_format($summary['total_potongan'] ?? 0, 0, ',', '.') }}
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
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gaji Bersih</p>
                        <p class="text-lg font-bold text-primary-600 dark:text-primary-400">
                            Rp {{ number_format($summary['total_gaji_bersih'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif

    {{-- Data Table --}}
    <x-filament::section icon="heroicon-o-document-text" icon-color="info">
        <x-slot name="heading">
            Data Slip Gaji
        </x-slot>

        <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">NIP</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Nama</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Gaji Pokok</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Tunjangan</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Potongan</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Gaji Bersih</th>
                        <th class="fi-ta-header-cell px-4 py-3 text-center text-sm font-semibold text-gray-950 dark:text-white">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-sm font-mono text-gray-950 dark:text-white">{{ $item['nip'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $item['nama'] }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm text-gray-950 dark:text-white">Rp {{ number_format($item['gaji_pokok'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm font-medium text-success-600 dark:text-success-400">Rp {{ number_format($item['total_tunjangan'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm font-medium text-danger-600 dark:text-danger-400">Rp {{ number_format($item['total_potongan'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-end text-sm font-bold text-gray-950 dark:text-white">Rp {{ number_format($item['gaji_bersih'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-4 py-3 text-center text-sm">
                                @if($item['status'] === 'dibayar')
                                    <x-filament::badge color="success">Dibayar</x-filament::badge>
                                @elseif($item['status'] === 'disetujui')
                                    <x-filament::badge color="info">Disetujui</x-filament::badge>
                                @else
                                    <x-filament::badge color="gray">Draft</x-filament::badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-inbox class="h-12 w-12 mb-2" />
                                    <p class="text-sm">Tidak ada data. Silakan pilih bulan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
