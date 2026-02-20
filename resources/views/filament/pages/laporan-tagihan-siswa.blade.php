<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Data
        </x-slot>
        <x-slot name="description">
            Pilih semester dan siswa untuk melihat tagihan
        </x-slot>

        {{ $this->filtersForm }}
    </x-filament::section>

    {{-- Data Table --}}
    <x-filament::section icon="heroicon-o-table-cells" icon-color="info">
        <x-slot name="heading">
            Daftar Tagihan Siswa
        </x-slot>

        <div class="-mx-6 overflow-x-auto">
            <table class="fi-ta-table min-w-[900px] w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-start text-sm font-semibold text-gray-950 dark:text-white">No. Tagihan</th>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-start text-sm font-semibold text-gray-950 dark:text-white">NIS</th>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Nama</th>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Kelas</th>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Jenis</th>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Tagihan</th>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Terbayar</th>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Sisa</th>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-center text-sm font-semibold text-gray-950 dark:text-white">Jatuh Tempo</th>
                        <th class="fi-ta-header-cell px-2 py-3 sm:px-3 text-center text-sm font-semibold text-gray-950 dark:text-white">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 whitespace-nowrap text-sm font-mono text-gray-950 dark:text-white">{{ $item['nomor_tagihan'] }}</td>
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 whitespace-nowrap text-sm text-gray-950 dark:text-white">{{ $item['nis'] }}</td>
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 text-sm font-medium text-gray-950 dark:text-white">{{ $item['nama'] }}</td>
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 text-sm text-gray-950 dark:text-white">{{ $item['kelas'] }}</td>
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 text-sm text-gray-950 dark:text-white">{{ $item['jenis'] }}</td>
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 whitespace-nowrap text-end text-sm text-gray-950 dark:text-white">Rp {{ number_format($item['total_tagihan'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 whitespace-nowrap text-end text-sm font-medium text-success-600 dark:text-success-400">Rp {{ number_format($item['terbayar'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 whitespace-nowrap text-end text-sm font-medium text-danger-600 dark:text-danger-400">Rp {{ number_format($item['sisa'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 whitespace-nowrap text-center text-sm text-gray-950 dark:text-white">{{ $item['jatuh_tempo'] }}</td>
                            <td class="fi-ta-cell px-2 py-3 sm:px-3 whitespace-nowrap text-center text-sm">
                                @if($item['status'] === 'lunas')
                                    <x-filament::badge color="success">Lunas</x-filament::badge>
                                @elseif($item['status'] === 'sebagian')
                                    <x-filament::badge color="warning">Sebagian</x-filament::badge>
                                @else
                                    <x-filament::badge color="danger">Belum Bayar</x-filament::badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-8 text-center">
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
