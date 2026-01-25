<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Data
        </x-slot>
        <x-slot name="description">
            Pilih semester untuk melihat rekap pembayaran
        </x-slot>

        {{ $this->filtersForm }}
    </x-filament::section>

    {{-- Data Table --}}
    <x-filament::section icon="heroicon-o-table-cells" icon-color="info">
        <x-slot name="heading">
            Rekap Per Jenis Pembayaran
        </x-slot>
        <x-slot name="description">
            Rincian pembayaran berdasarkan jenis pembayaran
        </x-slot>

        <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Jenis Pembayaran</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Siswa</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Total Tagihan</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Terbayar</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Sisa</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Lunas</th>
                        <th class="fi-ta-header-cell px-3 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Belum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                    @forelse($data as $index => $item)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-3 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $item['jenis'] }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-end text-sm text-gray-950 dark:text-white">{{ $item['jumlah_siswa'] }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-end text-sm text-gray-950 dark:text-white">Rp {{ number_format($item['total_tagihan'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-end text-sm font-medium text-success-600 dark:text-success-400">Rp {{ number_format($item['total_terbayar'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-end text-sm font-medium text-danger-600 dark:text-danger-400">Rp {{ number_format($item['total_sisa'], 0, ',', '.') }}</td>
                            <td class="fi-ta-cell px-3 py-3 text-end text-sm text-gray-950 dark:text-white">
                                <x-filament::badge color="success">{{ $item['lunas'] }}</x-filament::badge>
                            </td>
                            <td class="fi-ta-cell px-3 py-3 text-end text-sm text-gray-950 dark:text-white">
                                <x-filament::badge color="danger">{{ $item['belum_lunas'] }}</x-filament::badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-inbox" class="h-12 w-12 mb-2" />
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
