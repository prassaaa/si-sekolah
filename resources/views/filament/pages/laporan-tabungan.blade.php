<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        {{ $this->filtersForm }}
    </x-filament::section>

    @if($summary)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-filament::section>
                <x-slot name="heading">Total Siswa</x-slot>
                <p class="text-2xl font-bold text-gray-600">{{ $summary['total_siswa'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Setoran</x-slot>
                <p class="text-xl font-bold text-success-600">Rp {{ number_format($summary['total_setor'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Penarikan</x-slot>
                <p class="text-xl font-bold text-danger-600">Rp {{ number_format($summary['total_tarik'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Saldo</x-slot>
                <p class="text-xl font-bold text-primary-600">Rp {{ number_format($summary['total_saldo'] ?? 0, 0, ',', '.') }}</p>
            </x-filament::section>
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">Rekap Tabungan Per Siswa</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">NIS</th>
                        <th class="px-4 py-2 text-left">Nama Siswa</th>
                        <th class="px-4 py-2 text-left">Kelas</th>
                        <th class="px-4 py-2 text-right">Setoran</th>
                        <th class="px-4 py-2 text-right">Penarikan</th>
                        <th class="px-4 py-2 text-right">Saldo</th>
                        <th class="px-4 py-2 text-right">Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                        <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2">{{ $index + 1 }}</td>
                            <td class="px-4 py-2">{{ $item['nis'] }}</td>
                            <td class="px-4 py-2 font-medium">{{ $item['nama'] }}</td>
                            <td class="px-4 py-2">{{ $item['kelas'] }}</td>
                            <td class="px-4 py-2 text-right text-success-600">Rp {{ number_format($item['total_setor'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-danger-600">Rp {{ number_format($item['total_tarik'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right font-bold">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">{{ $item['jml_transaksi'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada data tabungan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
