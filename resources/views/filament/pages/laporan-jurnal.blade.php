<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        {{ $this->filtersForm }}
    </x-filament::section>

    @if($summary)
        <div class="grid grid-cols-3 gap-4">
            <x-filament::section>
                <x-slot name="heading">Total Transaksi</x-slot>
                <p class="text-2xl font-bold text-gray-600">{{ $summary['total_transaksi'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Debit</x-slot>
                <p class="text-xl font-bold text-success-600">Rp {{ number_format($summary['total_debit'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Kredit</x-slot>
                <p class="text-xl font-bold text-danger-600">Rp {{ number_format($summary['total_kredit'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">Data Jurnal Umum</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Tanggal</th>
                        <th class="px-4 py-2 text-left">No. Bukti</th>
                        <th class="px-4 py-2 text-left">Akun</th>
                        <th class="px-4 py-2 text-left">Keterangan</th>
                        <th class="px-4 py-2 text-right">Debit</th>
                        <th class="px-4 py-2 text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                        <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2">{{ $index + 1 }}</td>
                            <td class="px-4 py-2">{{ $item['tanggal'] }}</td>
                            <td class="px-4 py-2">{{ $item['nomor_bukti'] }}</td>
                            <td class="px-4 py-2 font-medium">{{ $item['akun'] }}</td>
                            <td class="px-4 py-2">{{ $item['keterangan'] }}</td>
                            <td class="px-4 py-2 text-right text-success-600">{{ $item['debit'] > 0 ? 'Rp ' . number_format($item['debit'], 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-2 text-right text-danger-600">{{ $item['kredit'] > 0 ? 'Rp ' . number_format($item['kredit'], 0, ',', '.') : '-' }}</td>
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
                            <td colspan="5" class="px-4 py-2">Total</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($summary['total_debit'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($summary['total_kredit'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
