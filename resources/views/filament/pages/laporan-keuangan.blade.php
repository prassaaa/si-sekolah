<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Periode
        </x-slot>
        <x-slot name="description">
            Pilih rentang tanggal untuk melihat laporan keuangan
        </x-slot>

        <form wire:submit="filter">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
                    Terapkan Filter
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-filament::section>
            <div class="flex items-center gap-x-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/10">
                    <x-heroicon-o-banknotes class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Tagihan</p>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        Rp {{ number_format($summary['total_tagihan'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-x-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 dark:bg-success-500/10">
                    <x-heroicon-o-check-circle class="h-6 w-6 text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pembayaran</p>
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400">
                        Rp {{ number_format($summary['total_pembayaran'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-x-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-50 dark:bg-info-500/10">
                    <x-heroicon-o-document-check class="h-6 w-6 text-info-600 dark:text-info-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tagihan Lunas</p>
                    <p class="text-2xl font-bold text-info-600 dark:text-info-400">
                        {{ number_format($summary['tagihan_lunas'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-x-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-danger-50 dark:bg-danger-500/10">
                    <x-heroicon-o-clock class="h-6 w-6 text-danger-600 dark:text-danger-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum Lunas</p>
                    <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">
                        {{ number_format($summary['tagihan_belum_lunas'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>

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
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                Metode Pembayaran
                            </th>
                            <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                        @foreach($summary['pembayaran_per_metode'] as $metode => $total)
                            <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell px-4 py-3 text-sm text-gray-950 dark:text-white">
                                    <div class="flex items-center gap-x-2">
                                        @switch($metode)
                                            @case('tunai')
                                                <x-heroicon-o-banknotes class="h-5 w-5 text-success-500" />
                                                <span>Tunai</span>
                                                @break
                                            @case('transfer')
                                                <x-heroicon-o-building-library class="h-5 w-5 text-info-500" />
                                                <span>Transfer Bank</span>
                                                @break
                                            @case('qris')
                                                <x-heroicon-o-qr-code class="h-5 w-5 text-primary-500" />
                                                <span>QRIS</span>
                                                @break
                                            @case('virtual_account')
                                                <x-heroicon-o-device-phone-mobile class="h-5 w-5 text-warning-500" />
                                                <span>Virtual Account</span>
                                                @break
                                            @default
                                                <x-heroicon-o-credit-card class="h-5 w-5 text-gray-500" />
                                                <span>{{ ucfirst($metode) }}</span>
                                        @endswitch
                                    </div>
                                </td>
                                <td class="fi-ta-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">
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
