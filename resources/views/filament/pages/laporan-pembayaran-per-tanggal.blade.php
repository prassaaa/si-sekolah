<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        {{ $this->filtersForm }}
    </x-filament::section>

    @if($summary)
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <x-filament::section>
                <x-slot name="heading">Total Transaksi</x-slot>
                <p class="text-2xl font-bold text-gray-600">{{ $summary['total_transaksi'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Tunai</x-slot>
                <p class="text-lg font-bold text-success-600">Rp {{ number_format($summary['total_tunai'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Transfer</x-slot>
                <p class="text-lg font-bold text-info-600">Rp {{ number_format($summary['total_transfer'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Lainnya</x-slot>
                <p class="text-lg font-bold text-warning-600">Rp {{ number_format($summary['total_lainnya'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Grand Total</x-slot>
                <p class="text-lg font-bold text-primary-600">Rp {{ number_format($summary['grand_total'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">Rekap Pembayaran Per Tanggal</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Tanggal</th>
                        <th class="px-4 py-2 text-right">Jumlah Transaksi</th>
                        <th class="px-4 py-2 text-right">Tunai</th>
                        <th class="px-4 py-2 text-right">Transfer</th>
                        <th class="px-4 py-2 text-right">Lainnya</th>
                        <th class="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                        <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2">{{ $index + 1 }}</td>
                            <td class="px-4 py-2 font-medium">{{ \Carbon\Carbon::parse($item['tanggal'])->format('d M Y') }}</td>
                            <td class="px-4 py-2 text-right">{{ $item['jumlah_transaksi'] }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($item['tunai'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($item['transfer'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($item['lainnya'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right font-bold">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada data. Silakan pilih rentang tanggal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data->count() > 0)
                    <tfoot>
                        <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                            <td colspan="2" class="px-4 py-2">Total</td>
                            <td class="px-4 py-2 text-right">{{ $summary['total_transaksi'] ?? 0 }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($summary['total_tunai'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($summary['total_transfer'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($summary['total_lainnya'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($summary['grand_total'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
