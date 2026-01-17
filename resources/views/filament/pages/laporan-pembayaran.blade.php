<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        {{ $this->filtersForm }}
    </x-filament::section>

    @if($summary)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-filament::section>
                <x-slot name="heading">Total Tagihan</x-slot>
                <p class="text-2xl font-bold text-gray-600">Rp {{ number_format($summary['total_tagihan'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Terbayar</x-slot>
                <p class="text-2xl font-bold text-success-600">Rp {{ number_format($summary['total_terbayar'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Sisa Tagihan</x-slot>
                <p class="text-2xl font-bold text-danger-600">Rp {{ number_format($summary['total_sisa'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Persentase Terbayar</x-slot>
                <p class="text-2xl font-bold text-primary-600">{{ $summary['persentase'] ?? 0 }}%</p>
            </x-filament::section>
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">Rekap Per Jenis Pembayaran</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Jenis Pembayaran</th>
                        <th class="px-4 py-2 text-right">Jumlah Siswa</th>
                        <th class="px-4 py-2 text-right">Total Tagihan</th>
                        <th class="px-4 py-2 text-right">Terbayar</th>
                        <th class="px-4 py-2 text-right">Sisa</th>
                        <th class="px-4 py-2 text-right">Lunas</th>
                        <th class="px-4 py-2 text-right">Belum Lunas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                        <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2">{{ $index + 1 }}</td>
                            <td class="px-4 py-2 font-medium">{{ $item['jenis'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $item['jumlah_siswa'] }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($item['total_tagihan'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-success-600">Rp {{ number_format($item['total_terbayar'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-danger-600">Rp {{ number_format($item['total_sisa'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">{{ $item['lunas'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $item['belum_lunas'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada data. Silakan pilih semester.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
