<x-filament-panels::page>
    <form wire:submit="filter">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Filter
            </x-filament::button>
        </div>
    </form>

    <div class="mt-6">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Tagihan</p>
                    <p class="text-2xl font-bold text-primary-600">Rp {{ number_format($summary['total_tagihan'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Pembayaran</p>
                    <p class="text-2xl font-bold text-success-600">Rp {{ number_format($summary['total_pembayaran'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tagihan Lunas</p>
                    <p class="text-2xl font-bold text-info-600">{{ number_format($summary['tagihan_lunas'] ?? 0) }}</p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tagihan Belum Lunas</p>
                    <p class="text-2xl font-bold text-danger-600">{{ number_format($summary['tagihan_belum_lunas'] ?? 0) }}</p>
                </div>
            </x-filament::section>
        </div>

        @if(!empty($summary['pembayaran_per_metode']))
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    Pembayaran per Metode
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="py-2 px-4">Metode</th>
                                <th class="py-2 px-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary['pembayaran_per_metode'] as $metode => $total)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="py-2 px-4">
                                        @switch($metode)
                                            @case('tunai') Tunai @break
                                            @case('transfer') Transfer Bank @break
                                            @case('qris') QRIS @break
                                            @case('virtual_account') Virtual Account @break
                                            @default {{ $metode }}
                                        @endswitch
                                    </td>
                                    <td class="py-2 px-4 text-right font-semibold">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
