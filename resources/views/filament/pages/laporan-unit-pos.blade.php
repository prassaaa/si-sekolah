<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        {{ $this->filtersForm }}
    </x-filament::section>

    @if($summary)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-filament::section>
                <x-slot name="heading">Unit POS</x-slot>
                <p class="text-xl font-bold text-primary-600">{{ $unitPosNama ?? 'Semua Unit' }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Unit</x-slot>
                <p class="text-2xl font-bold text-gray-600">{{ $summary['total_unit'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Transaksi</x-slot>
                <p class="text-2xl font-bold text-info-600">{{ $summary['total_transaksi'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Nominal</x-slot>
                <p class="text-xl font-bold text-success-600">Rp {{ number_format($summary['total_nominal'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
        </div>
    @endif

    {{-- Summary per Unit --}}
    @if($data->count() > 0)
        <x-filament::section>
            <x-slot name="heading">Rekap Per Unit POS</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-2 text-left">#</th>
                            <th class="px-4 py-2 text-left">Kode</th>
                            <th class="px-4 py-2 text-left">Nama Unit</th>
                            <th class="px-4 py-2 text-left">Alamat</th>
                            <th class="px-4 py-2 text-right">Transaksi</th>
                            <th class="px-4 py-2 text-right">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $index => $item)
                            <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2 font-mono">{{ $item['kode'] }}</td>
                                <td class="px-4 py-2 font-medium">{{ $item['nama'] }}</td>
                                <td class="px-4 py-2">{{ $item['alamat'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $item['total_transaksi'] }}</td>
                                <td class="px-4 py-2 text-right font-bold text-success-600">Rp {{ number_format($item['total_nominal'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- Detail Transaksi --}}
    <x-filament::section>
        <x-slot name="heading">Detail Transaksi</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 dark:bg-gray-800">
                        <th class="px-3 py-2 text-left">#</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">No. Transaksi</th>
                        <th class="px-3 py-2 text-left">Siswa</th>
                        <th class="px-3 py-2 text-left">Jenis Bayar</th>
                        <th class="px-3 py-2 text-left">Metode</th>
                        <th class="px-3 py-2 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksiData as $index => $item)
                        <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-3 py-2">{{ $index + 1 }}</td>
                            <td class="px-3 py-2">{{ $item['tanggal'] }}</td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $item['nomor_transaksi'] }}</td>
                            <td class="px-3 py-2 font-medium">{{ $item['siswa'] }}</td>
                            <td class="px-3 py-2">{{ $item['jenis'] }}</td>
                            <td class="px-3 py-2">{{ $item['metode'] }}</td>
                            <td class="px-3 py-2 text-right font-medium">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-gray-500">
                                Tidak ada transaksi dalam periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($transaksiData->count() > 0)
                    <tfoot>
                        <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                            <td colspan="6" class="px-3 py-2">Total</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($summary['total_nominal'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
