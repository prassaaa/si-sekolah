<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Data
        </x-slot>
        <x-slot name="description">
            Pilih kelas untuk melihat data tabungan siswa
        </x-slot>

        {{ $this->filtersForm }}
    </x-filament::section>

    {{-- Data Table --}}
    <x-filament::section icon="heroicon-o-banknotes" icon-color="success">
        <x-slot name="heading">
            Rekap Tabungan Per Siswa
        </x-slot>

        <div class="fi-ta-content relative divide-y divide-gray-200 dark:divide-white/10" style="overflow-x: auto">
            <table class="fi-ta-table min-w-[700px] w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">NIS</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Nama Siswa</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Kelas</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">Setoran</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">Penarikan</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">Saldo</th>
                        <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white whitespace-nowrap">Transaksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-sm font-mono text-gray-950 dark:text-white whitespace-nowrap">{{ $item['nis'] }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $item['nama'] }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-sm text-gray-950 dark:text-white">{{ $item['kelas'] }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-end text-sm font-medium text-success-600 dark:text-success-400 whitespace-nowrap">Rp {{ number_format($item['total_setor'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-end text-sm font-medium text-danger-600 dark:text-danger-400 whitespace-nowrap">Rp {{ number_format($item['total_tarik'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-end text-sm font-bold text-primary-600 dark:text-primary-400 whitespace-nowrap">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-3 sm:px-4 py-3 text-end text-sm text-gray-950 dark:text-white whitespace-nowrap">
                                <x-filament::badge color="gray">{{ $item['jml_transaksi'] }}</x-filament::badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 sm:px-4 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-inbox" class="mb-2 h-12 w-12" />
                                    <p class="text-sm">Tidak ada data tabungan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
