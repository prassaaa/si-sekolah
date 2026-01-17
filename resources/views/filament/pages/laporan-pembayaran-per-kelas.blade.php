<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        {{ $this->filtersForm }}
    </x-filament::section>

    @if($summary && $kelasNama)
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <x-filament::section>
                <x-slot name="heading">Kelas</x-slot>
                <p class="text-xl font-bold text-primary-600">{{ $kelasNama }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Siswa</x-slot>
                <p class="text-2xl font-bold text-gray-600">{{ $summary['total_siswa'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Tagihan</x-slot>
                <p class="text-lg font-bold text-gray-600">Rp {{ number_format($summary['total_tagihan'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Terbayar</x-slot>
                <p class="text-lg font-bold text-success-600">Rp {{ number_format($summary['total_terbayar'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Lunas</x-slot>
                <p class="text-2xl font-bold text-success-600">{{ $summary['lunas'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Belum Lunas</x-slot>
                <p class="text-2xl font-bold text-danger-600">{{ $summary['belum_lunas'] ?? 0 }}</p>
            </x-filament::section>
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">Data Pembayaran Siswa</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">NIS</th>
                        <th class="px-4 py-2 text-left">Nama Siswa</th>
                        <th class="px-4 py-2 text-right">Total Tagihan</th>
                        <th class="px-4 py-2 text-right">Terbayar</th>
                        <th class="px-4 py-2 text-right">Sisa</th>
                        <th class="px-4 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                        <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2">{{ $index + 1 }}</td>
                            <td class="px-4 py-2">{{ $item['nis'] }}</td>
                            <td class="px-4 py-2 font-medium">{{ $item['nama'] }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($item['total_tagihan'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-success-600">Rp {{ number_format($item['total_terbayar'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-danger-600">Rp {{ number_format($item['sisa'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($item['status'] === 'Lunas')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-success-700 bg-success-100 rounded-full">Lunas</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-danger-700 bg-danger-100 rounded-full">Belum Lunas</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada data. Silakan pilih semester dan kelas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
