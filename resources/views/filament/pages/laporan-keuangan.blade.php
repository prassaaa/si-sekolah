<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Periode
        </x-slot>
        <x-slot name="description">
            Pilih rentang tanggal untuk melihat laporan keuangan
        </x-slot>

        {{ $this->filtersForm }}
    </x-filament::section>

    {{-- Pembayaran per Metode --}}
    @if(!empty($summary['pembayaran_per_metode']))
        <x-filament::section icon="heroicon-o-credit-card" icon-color="success">
            <x-slot name="heading">
                Pembayaran per Metode
            </x-slot>
            <x-slot name="description">
                Rincian pembayaran berdasarkan metode pembayaran
            </x-slot>

            <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 dark:border-t-white/10">
                <table class="fi-ta-table min-w-[500px] w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                Metode Pembayaran
                            </th>
                            <th class="fi-ta-header-cell px-3 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($summary['pembayaran_per_metode'] as $metode => $total)
                            <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell whitespace-nowrap px-3 sm:px-4 py-3 text-sm text-gray-950 dark:text-white">
                                    @switch($metode)
                                        @case('tunai')
                                            <x-filament::badge color="success" icon="heroicon-o-banknotes">
                                                Tunai
                                            </x-filament::badge>
                                            @break
                                        @case('transfer')
                                            <x-filament::badge color="info" icon="heroicon-o-building-library">
                                                Transfer Bank
                                            </x-filament::badge>
                                            @break
                                        @case('qris')
                                            <x-filament::badge color="primary" icon="heroicon-o-qr-code">
                                                QRIS
                                            </x-filament::badge>
                                            @break
                                        @case('virtual_account')
                                            <x-filament::badge color="warning" icon="heroicon-o-device-phone-mobile">
                                                Virtual Account
                                            </x-filament::badge>
                                            @break
                                        @default
                                            <x-filament::badge color="gray" icon="heroicon-o-credit-card">
                                                {{ ucfirst($metode) }}
                                            </x-filament::badge>
                                    @endswitch
                                </td>
                                <td class="fi-ta-cell whitespace-nowrap px-3 sm:px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">
                                    Rp {{ number_format($total, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
