<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        {{ $this->filtersForm }}
    </x-filament::section>

    @if($summary)
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <x-filament::section>
                <x-slot name="heading">Total Pegawai</x-slot>
                <p class="text-2xl font-bold text-gray-600">{{ $summary['total_pegawai'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Gaji Pokok</x-slot>
                <p class="text-lg font-bold text-gray-600">Rp {{ number_format($summary['total_gaji_pokok'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Tunjangan</x-slot>
                <p class="text-lg font-bold text-success-600">Rp {{ number_format($summary['total_tunjangan'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Potongan</x-slot>
                <p class="text-lg font-bold text-danger-600">Rp {{ number_format($summary['total_potongan'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Gaji Bersih</x-slot>
                <p class="text-lg font-bold text-primary-600">Rp {{ number_format($summary['total_gaji_bersih'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">Data Slip Gaji</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">NIP</th>
                        <th class="px-4 py-2 text-left">Nama</th>
                        <th class="px-4 py-2 text-right">Gaji Pokok</th>
                        <th class="px-4 py-2 text-right">Tunjangan</th>
                        <th class="px-4 py-2 text-right">Potongan</th>
                        <th class="px-4 py-2 text-right">Gaji Bersih</th>
                        <th class="px-4 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                        <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2">{{ $index + 1 }}</td>
                            <td class="px-4 py-2">{{ $item['nip'] }}</td>
                            <td class="px-4 py-2 font-medium">{{ $item['nama'] }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($item['gaji_pokok'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-success-600">Rp {{ number_format($item['total_tunjangan'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-danger-600">Rp {{ number_format($item['total_potongan'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right font-bold">Rp {{ number_format($item['gaji_bersih'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($item['status'] === 'dibayar')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-success-700 bg-success-100 rounded-full">Dibayar</span>
                                @elseif($item['status'] === 'disetujui')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-info-700 bg-info-100 rounded-full">Disetujui</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-full">Draft</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada data. Silakan pilih bulan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
