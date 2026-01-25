<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Data
        </x-slot>
        <x-slot name="description">
            Pilih rentang tanggal dan jenis transaksi untuk melihat laporan kas
        </x-slot>

        {{ $this->filtersForm }}
    </x-filament::section>

    {{-- Stats Cards --}}
    @if($summary)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 dark:bg-success-500/10">
                        <x-heroicon-o-arrow-down-tray class="h-6 w-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Masuk</p>
                        <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $summary['jml_masuk'] ?? 0 }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-50 dark:bg-danger-500/10">
                        <x-heroicon-o-arrow-up-tray class="h-6 w-6 text-danger-600 dark:text-danger-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Keluar</p>
                        <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $summary['jml_keluar'] ?? 0 }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 dark:bg-success-500/10">
                        <x-heroicon-o-plus-circle class="h-6 w-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Kas Masuk</p>
                        <p class="text-lg font-bold text-success-600 dark:text-success-400">
                            Rp {{ number_format($summary['total_masuk'] ?? 0, 0, ',', '.') }}
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
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Kas Keluar</p>
                        <p class="text-lg font-bold text-danger-600 dark:text-danger-400">
                            Rp {{ number_format($summary['total_keluar'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg {{ ($summary['selisih'] ?? 0) >= 0 ? 'bg-success-50 dark:bg-success-500/10' : 'bg-danger-50 dark:bg-danger-500/10' }}">
                        <x-heroicon-o-scale class="h-6 w-6 {{ ($summary['selisih'] ?? 0) >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Selisih</p>
                        <p class="text-lg font-bold {{ ($summary['selisih'] ?? 0) >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                            Rp {{ number_format($summary['selisih'] ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif

    {{-- Data Tables --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Kas Masuk --}}
        @if($jenis === 'all' || $jenis === 'masuk')
            <x-filament::section icon="heroicon-o-arrow-trending-up" icon-color="success">
                <x-slot name="heading">
                    Kas Masuk (Debit)
                </x-slot>

                <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="bg-success-50 dark:bg-success-500/10">
                            <tr>
                                <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Tanggal</th>
                                <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">No. Bukti</th>
                                <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Sumber</th>
                                <th class="fi-ta-header-cell px-3 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                            @forelse($kasMasukData as $item)
                                <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="fi-ta-cell px-3 py-3 text-sm text-gray-950 dark:text-white">{{ $item['tanggal'] }}</td>
                                    <td class="fi-ta-cell px-3 py-3 text-sm font-mono text-xs text-gray-950 dark:text-white">{{ $item['nomor_bukti'] }}</td>
                                    <td class="fi-ta-cell px-3 py-3 text-sm text-gray-950 dark:text-white">{{ $item['sumber'] }}</td>
                                    <td class="fi-ta-cell px-3 py-3 text-end text-sm font-medium text-success-600 dark:text-success-400">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                            <x-heroicon-o-inbox class="h-8 w-8 mb-1" />
                                            <p class="text-sm">Tidak ada data</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($kasMasukData->count() > 0)
                            <tfoot class="bg-success-100 dark:bg-success-500/20">
                                <tr>
                                    <td colspan="3" class="px-3 py-3 text-sm font-bold text-gray-950 dark:text-white">Total</td>
                                    <td class="px-3 py-3 text-end text-sm font-bold text-success-600 dark:text-success-400">Rp {{ number_format($summary['total_masuk'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Kas Keluar --}}
        @if($jenis === 'all' || $jenis === 'keluar')
            <x-filament::section icon="heroicon-o-arrow-trending-down" icon-color="danger">
                <x-slot name="heading">
                    Kas Keluar (Kredit)
                </x-slot>

                <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="bg-danger-50 dark:bg-danger-500/10">
                            <tr>
                                <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Tanggal</th>
                                <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">No. Bukti</th>
                                <th class="fi-ta-header-cell px-3 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Penerima</th>
                                <th class="fi-ta-header-cell px-3 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                            @forelse($kasKeluarData as $item)
                                <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="fi-ta-cell px-3 py-3 text-sm text-gray-950 dark:text-white">{{ $item['tanggal'] }}</td>
                                    <td class="fi-ta-cell px-3 py-3 text-sm font-mono text-xs text-gray-950 dark:text-white">{{ $item['nomor_bukti'] }}</td>
                                    <td class="fi-ta-cell px-3 py-3 text-sm text-gray-950 dark:text-white">{{ $item['penerima'] }}</td>
                                    <td class="fi-ta-cell px-3 py-3 text-end text-sm font-medium text-danger-600 dark:text-danger-400">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                            <x-heroicon-o-inbox class="h-8 w-8 mb-1" />
                                            <p class="text-sm">Tidak ada data</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($kasKeluarData->count() > 0)
                            <tfoot class="bg-danger-100 dark:bg-danger-500/20">
                                <tr>
                                    <td colspan="3" class="px-3 py-3 text-sm font-bold text-gray-950 dark:text-white">Total</td>
                                    <td class="px-3 py-3 text-end text-sm font-bold text-danger-600 dark:text-danger-400">Rp {{ number_format($summary['total_keluar'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
