<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        {{ $this->filtersForm }}
    </x-filament::section>

    @if($summary)
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <x-filament::section>
                <x-slot name="heading">Transaksi Masuk</x-slot>
                <p class="text-2xl font-bold text-gray-600">{{ $summary['jml_masuk'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Transaksi Keluar</x-slot>
                <p class="text-2xl font-bold text-gray-600">{{ $summary['jml_keluar'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Kas Masuk</x-slot>
                <p class="text-xl font-bold text-success-600">Rp {{ number_format($summary['total_masuk'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Kas Keluar</x-slot>
                <p class="text-xl font-bold text-danger-600">Rp {{ number_format($summary['total_keluar'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Selisih</x-slot>
                <p class="text-xl font-bold {{ ($summary['selisih'] ?? 0) >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                    Rp {{ number_format($summary['selisih'] ?? 0, 0, ',', '.') }}
                </p>
            </x-filament::section>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Kas Masuk --}}
        @if($jenis === 'all' || $jenis === 'masuk')
            <x-filament::section>
                <x-slot name="heading">Kas Masuk (Debit)</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-green-50 dark:bg-green-900">
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-left">No. Bukti</th>
                                <th class="px-3 py-2 text-left">Sumber</th>
                                <th class="px-3 py-2 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kasMasukData as $item)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-3 py-2">{{ $item['tanggal'] }}</td>
                                    <td class="px-3 py-2">{{ $item['nomor_bukti'] }}</td>
                                    <td class="px-3 py-2">{{ $item['sumber'] }}</td>
                                    <td class="px-3 py-2 text-right font-medium text-success-600">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center text-gray-500">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($kasMasukData->count() > 0)
                            <tfoot>
                                <tr class="bg-green-100 dark:bg-green-800 font-bold">
                                    <td colspan="3" class="px-3 py-2">Total</td>
                                    <td class="px-3 py-2 text-right">Rp {{ number_format($summary['total_masuk'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Kas Keluar --}}
        @if($jenis === 'all' || $jenis === 'keluar')
            <x-filament::section>
                <x-slot name="heading">Kas Keluar (Kredit)</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-red-50 dark:bg-red-900">
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-left">No. Bukti</th>
                                <th class="px-3 py-2 text-left">Penerima</th>
                                <th class="px-3 py-2 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kasKeluarData as $item)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-3 py-2">{{ $item['tanggal'] }}</td>
                                    <td class="px-3 py-2">{{ $item['nomor_bukti'] }}</td>
                                    <td class="px-3 py-2">{{ $item['penerima'] }}</td>
                                    <td class="px-3 py-2 text-right font-medium text-danger-600">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center text-gray-500">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($kasKeluarData->count() > 0)
                            <tfoot>
                                <tr class="bg-red-100 dark:bg-red-800 font-bold">
                                    <td colspan="3" class="px-3 py-2">Total</td>
                                    <td class="px-3 py-2 text-right">Rp {{ number_format($summary['total_keluar'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
