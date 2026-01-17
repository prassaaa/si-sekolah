<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filter</x-slot>
        {{ $this->filtersForm }}
    </x-filament::section>

    @if($summary)
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <x-filament::section>
                <x-slot name="heading">Total Siswa</x-slot>
                <p class="text-2xl font-bold text-primary-600">{{ $summary['total_siswa'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Setoran</x-slot>
                <p class="text-2xl font-bold text-success-600">{{ $summary['total_setoran'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Muroja'ah</x-slot>
                <p class="text-2xl font-bold text-info-600">{{ $summary['total_murojaah'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Total Ayat</x-slot>
                <p class="text-2xl font-bold text-warning-600">{{ $summary['total_ayat'] ?? 0 }}</p>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Rata-rata Nilai</x-slot>
                <p class="text-2xl font-bold text-gray-600">{{ $summary['rata_rata_nilai'] ?? 0 }}</p>
            </x-filament::section>
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">Data Rekap Tahfidz</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Nama Siswa</th>
                        <th class="px-4 py-2 text-left">Kelas</th>
                        <th class="px-4 py-2 text-right">Setoran</th>
                        <th class="px-4 py-2 text-right">Muroja'ah</th>
                        <th class="px-4 py-2 text-right">Total Ayat</th>
                        <th class="px-4 py-2 text-right">Rata-rata Nilai</th>
                        <th class="px-4 py-2 text-right">Lulus</th>
                        <th class="px-4 py-2 text-right">Belum Lulus</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                        <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2">{{ $index + 1 }}</td>
                            <td class="px-4 py-2 font-medium">{{ $item['siswa'] }}</td>
                            <td class="px-4 py-2">{{ $item['kelas'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $item['total_setoran'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $item["total_muroja'ah"] }}</td>
                            <td class="px-4 py-2 text-right">{{ $item['total_ayat'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $item['rata_rata_nilai'] }}</td>
                            <td class="px-4 py-2 text-right text-success-600">{{ $item['lulus'] }}</td>
                            <td class="px-4 py-2 text-right text-danger-600">{{ $item['belum_lulus'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada data. Silakan pilih semester.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
