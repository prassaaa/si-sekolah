<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        {{ $this->filtersForm }}
    </x-filament::section>

    @if($summary)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-filament::section>
                <x-slot name="heading">Jumlah Tagihan</x-slot>
                <p class="text-2xl font-bold text-gray-600">{{ $summary['jumlah_tagihan'] ?? 0 }}</p>
                <div class="text-xs mt-1 text-gray-500">
                    <span class="text-danger-600">{{ $summary['belum_bayar'] ?? 0 }} belum</span> |
                    <span class="text-warning-600">{{ $summary['sebagian'] ?? 0 }} sebagian</span> |
                    <span class="text-success-600">{{ $summary['lunas'] ?? 0 }} lunas</span>
                </div>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Tagihan</x-slot>
                <p class="text-xl font-bold text-gray-600">Rp {{ number_format($summary['total_tagihan'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Terbayar</x-slot>
                <p class="text-xl font-bold text-success-600">Rp {{ number_format($summary['total_terbayar'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Sisa Tagihan</x-slot>
                <p class="text-xl font-bold text-danger-600">Rp {{ number_format($summary['total_sisa'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">Daftar Tagihan Siswa</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 dark:bg-gray-800">
                        <th class="px-3 py-2 text-left">#</th>
                        <th class="px-3 py-2 text-left">No. Tagihan</th>
                        <th class="px-3 py-2 text-left">NIS</th>
                        <th class="px-3 py-2 text-left">Nama</th>
                        <th class="px-3 py-2 text-left">Kelas</th>
                        <th class="px-3 py-2 text-left">Jenis</th>
                        <th class="px-3 py-2 text-right">Tagihan</th>
                        <th class="px-3 py-2 text-right">Terbayar</th>
                        <th class="px-3 py-2 text-right">Sisa</th>
                        <th class="px-3 py-2 text-center">Jatuh Tempo</th>
                        <th class="px-3 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                        <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-3 py-2">{{ $index + 1 }}</td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $item['nomor_tagihan'] }}</td>
                            <td class="px-3 py-2">{{ $item['nis'] }}</td>
                            <td class="px-3 py-2 font-medium">{{ $item['nama'] }}</td>
                            <td class="px-3 py-2">{{ $item['kelas'] }}</td>
                            <td class="px-3 py-2">{{ $item['jenis'] }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($item['total_tagihan'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-success-600">Rp {{ number_format($item['terbayar'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-danger-600">Rp {{ number_format($item['sisa'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-center">{{ $item['jatuh_tempo'] }}</td>
                            <td class="px-3 py-2 text-center">
                                @if($item['status'] === 'lunas')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-success-700 bg-success-100 rounded-full">Lunas</span>
                                @elseif($item['status'] === 'sebagian')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-warning-700 bg-warning-100 rounded-full">Sebagian</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-danger-700 bg-danger-100 rounded-full">Belum Bayar</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-8 text-center text-gray-500">
                                Tidak ada data. Silakan pilih semester.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
